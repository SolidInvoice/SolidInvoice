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

namespace SolidInvoice\InvoiceBundle\Mcp;

use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Repository\ClientRepository;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Repository\InvoiceRepository;
use SolidInvoice\McpBundle\Mcp\Attribute\McpScopeRequired;
use SolidInvoice\McpBundle\Mcp\McpScopeGuard;
use SolidInvoice\McpBundle\Mcp\Tool\EntityNormalizer;
use SolidInvoice\McpBundle\Mcp\Tool\UlidParser;
use SolidInvoice\McpBundle\Security\McpScope;
use Symfony\Bridge\Doctrine\Types\UlidType;

final class InvoiceReadTools
{
    public function __construct(
        private readonly InvoiceRepository $invoiceRepository,
        private readonly ClientRepository $clientRepository,
        private readonly EntityNormalizer $normalizer,
        private readonly McpScopeGuard $scopeGuard,
    ) {
    }

    /**
     * List overdue invoices for the current company, optionally scoped to a single client.
     *
     * @param string|null $client_id Optional client ULID
     * @param int         $limit     Max rows (1..100)
     *
     * @return array{results: list<array<string, mixed>>, count: int}
     */
    #[McpTool(name: 'list_overdue_invoices', description: 'List overdue invoices, optionally for a specific client.')]
    #[McpScopeRequired(McpScope::Read)]
    public function listOverdueInvoices(?string $client_id = null, int $limit = 25): array
    {
        $this->scopeGuard->require(McpScope::Read);
        $limit = max(1, min($limit, 100));

        $qb = $this->invoiceRepository->createQueryBuilder('i')
            ->andWhere('i.status = :status')
            ->setParameter('status', InvoiceStatus::Overdue)
            ->orderBy('i.due', 'ASC')
            ->setMaxResults($limit);

        if ($client_id !== null) {
            $client = $this->clientRepository->find(UlidParser::parse($client_id, 'client_id'));

            if (! $client instanceof Client) {
                throw new ToolCallException(sprintf('Client %s not found.', $client_id));
            }

            $qb->andWhere('i.client = :client')->setParameter('client', $client->getId(), UlidType::NAME);
        }

        $results = $this->normalizer->normalizeMany($qb->getQuery()->getResult());

        return ['results' => $results, 'count' => count($results)];
    }

    /**
     * List invoices filtered by status, optionally scoped to a client.
     *
     * @param string      $status    One of: new, draft, pending, paid, active, overdue, cancelled, archived
     * @param string|null $client_id Optional client ULID
     * @param int         $limit     Max rows (1..100)
     *
     * @return array{results: list<array<string, mixed>>, count: int}
     */
    #[McpTool(name: 'list_invoices_by_status', description: 'List invoices filtered by status.')]
    #[McpScopeRequired(McpScope::Read)]
    public function listInvoicesByStatus(string $status, ?string $client_id = null, int $limit = 25): array
    {
        $this->scopeGuard->require(McpScope::Read);
        $limit = max(1, min($limit, 100));

        $statusEnum = InvoiceStatus::tryFrom($status);

        if ($statusEnum === null) {
            throw new ToolCallException(sprintf(
                'Invalid status "%s". Valid values: %s.',
                $status,
                implode(', ', array_map(static fn (InvoiceStatus $s): string => $s->value, InvoiceStatus::cases())),
            ));
        }

        $qb = $this->invoiceRepository->createQueryBuilder('i')
            ->andWhere('i.status = :status')
            ->setParameter('status', $statusEnum)
            ->orderBy('i.created', 'DESC')
            ->setMaxResults($limit);

        if ($client_id !== null) {
            $client = $this->clientRepository->find(UlidParser::parse($client_id, 'client_id'));

            if (! $client instanceof Client) {
                throw new ToolCallException(sprintf('Client %s not found.', $client_id));
            }

            $qb->andWhere('i.client = :client')->setParameter('client', $client->getId(), UlidType::NAME);
        }

        $results = $this->normalizer->normalizeMany($qb->getQuery()->getResult());

        return ['results' => $results, 'count' => count($results)];
    }
}
