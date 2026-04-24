<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\McpBundle\Mcp\Tool;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use SolidInvoice\McpBundle\Mcp\Attribute\McpScopeRequired;
use SolidInvoice\McpBundle\Mcp\McpScopeGuard;
use SolidInvoice\McpBundle\Security\McpScope;
use Symfony\Component\Uid\Ulid;

/**
 * Generic query tools for reading SolidInvoice business resources. Company
 * isolation is enforced by the active CompanyFilter — every query runs under
 * the token's bound company, set by {@see \SolidInvoice\McpBundle\Security\McpOAuthAuthenticator}.
 */
final class ResourceQueryTools
{
    public function __construct(
        private readonly ResourceRegistry $registry,
        private readonly EntityManagerInterface $entityManager,
        private readonly EntityNormalizer $normalizer,
        private readonly McpScopeGuard $scopeGuard,
    ) {
    }

    /**
     * List records of a SolidInvoice resource (invoices, quotes, clients, contacts,
     * payments, taxes, payment methods, recurring invoices).
     *
     * @param string               $resource       Resource name: invoice, recurring_invoice, quote, client, contact, payment, payment_method, tax
     * @param array<string, mixed> $filters        Optional exact-match filters on scalar fields (e.g. {"status": "overdue", "client_id": "01H..."})
     * @param int                  $page           1-indexed page number
     * @param int                  $items_per_page Results per page (max 100)
     * @param string|null          $order_by       Field name to sort by (e.g. "created")
     * @param string               $order          "asc" or "desc"
     *
     * @return array{results: list<array<string, mixed>>, page: int, items_per_page: int, total: int, resource: string}
     */
    #[McpTool(name: 'list_resource', description: 'List records of a SolidInvoice resource with optional filters and pagination.')]
    #[McpScopeRequired(McpScope::Read)]
    public function listResource(
        string $resource,
        array $filters = [],
        int $page = 1,
        int $items_per_page = 25,
        ?string $order_by = null,
        string $order = 'desc',
    ): array {
        $this->scopeGuard->require(McpScope::Read);

        $class = $this->registry->resolve($resource);
        $items_per_page = max(1, min($items_per_page, 100));
        $page = max(1, $page);

        $qb = $this->entityManager->createQueryBuilder()
            ->from($class, 'e')
            ->select('e');

        $metadata = $this->entityManager->getClassMetadata($class);
        $this->applyFilters($qb, $metadata, $filters);

        $countQb = (clone $qb)->select('COUNT(e)');
        $total = (int) $countQb->getQuery()->getSingleScalarResult();

        if ($order_by !== null) {
            $fieldNames = $metadata->getFieldNames();
            $orderField = $this->normaliseField($order_by, $fieldNames, $metadata->getAssociationNames(), $metadata);

            if ($orderField === null || ! \in_array($orderField, $fieldNames, true)) {
                throw new ToolCallException(sprintf('Unknown order_by field "%s".', $order_by));
            }

            $direction = strtolower($order) === 'asc' ? 'ASC' : 'DESC';
            $qb->orderBy('e.' . $orderField, $direction);
        }

        $qb->setFirstResult(($page - 1) * $items_per_page)
            ->setMaxResults($items_per_page);

        $results = $qb->getQuery()->getResult();

        return [
            'resource' => $resource,
            'page' => $page,
            'items_per_page' => $items_per_page,
            'total' => $total,
            'results' => $this->normalizer->normalizeMany($results),
        ];
    }

    /**
     * Fetch a single record of a SolidInvoice resource by its ULID.
     *
     * @param string $resource Resource name (see list_resource for supported values)
     * @param string $id       ULID of the record
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'get_resource', description: 'Fetch a single SolidInvoice record by ID.')]
    #[McpScopeRequired(McpScope::Read)]
    public function getResource(string $resource, string $id): array
    {
        $this->scopeGuard->require(McpScope::Read);

        $class = $this->registry->resolve($resource);

        try {
            $ulid = Ulid::fromString($id);
        } catch (\InvalidArgumentException) {
            throw new ToolCallException(sprintf('Invalid ULID: %s.', $id));
        }

        $entity = $this->entityManager->getRepository($class)->find($ulid);

        if ($entity === null) {
            throw new ToolCallException(sprintf('%s with id %s not found.', $resource, $id));
        }

        return $this->normalizer->normalize($entity);
    }

    /**
     * @param \Doctrine\ORM\Mapping\ClassMetadata<object> $metadata
     * @param array<string, mixed>                        $filters
     */
    private function applyFilters(QueryBuilder $qb, \Doctrine\ORM\Mapping\ClassMetadata $metadata, array $filters): void
    {
        if ($filters === []) {
            return;
        }

        $fieldNames = $metadata->getFieldNames();
        $associationNames = $metadata->getAssociationNames();
        $i = 0;

        foreach ($filters as $field => $value) {
            if (! \is_string($field)) {
                continue;
            }

            $actualField = $this->normaliseField($field, $fieldNames, $associationNames, $metadata);

            if ($actualField === null) {
                throw new ToolCallException(sprintf('Unknown filter field "%s" on resource.', $field));
            }

            ++$i;
            $paramName = 'f' . $i;

            if (\in_array($actualField, $associationNames, true)) {
                // Reject OneToMany/ManyToMany — e.<field> = :ulid only works
                // for owning-side singular associations; collection filters
                // would surface as a DQL QueryException to the MCP client.
                if (! $metadata->isSingleValuedAssociation($actualField)) {
                    throw new ToolCallException(sprintf(
                        'Filter "%s" targets a collection association and is not supported.',
                        $field,
                    ));
                }

                if (! \is_string($value)) {
                    throw new ToolCallException(sprintf('Filter "%s" must be a ULID string.', $field));
                }

                try {
                    $qb->andWhere(sprintf('e.%s = :%s', $actualField, $paramName))
                        ->setParameter($paramName, Ulid::fromString($value));

                    continue;
                } catch (\InvalidArgumentException) {
                    throw new ToolCallException(sprintf('Filter "%s" must be a valid ULID.', $field));
                }
            }

            if (\is_scalar($value) || $value === null) {
                $qb->andWhere(sprintf('e.%s = :%s', $actualField, $paramName))
                    ->setParameter($paramName, $value);

                continue;
            }

            throw new ToolCallException(sprintf('Filter "%s" must be a scalar value.', $field));
        }
    }

    /**
     * @param list<string>                                                              $fieldNames
     * @param list<string>                                                              $associationNames
     * @param \Doctrine\ORM\Mapping\ClassMetadata<object>                               $metadata
     */
    private function normaliseField(string $input, array $fieldNames, array $associationNames, \Doctrine\ORM\Mapping\ClassMetadata $metadata): ?string
    {
        if (\in_array($input, $fieldNames, true)) {
            return $input;
        }

        if (\in_array($input, $associationNames, true)) {
            return $input;
        }

        // Accept snake_case aliases for camelCase fields.
        $camel = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $input))));

        if (\in_array($camel, $fieldNames, true)) {
            return $camel;
        }

        if (\in_array($camel, $associationNames, true)) {
            return $camel;
        }

        // Accept short association aliases e.g. "client_id" → "client"
        if (str_ends_with($input, '_id')) {
            $candidate = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', substr($input, 0, -3)))));

            if (\in_array($candidate, $associationNames, true)) {
                return $candidate;
            }
        }

        return null;
    }
}
