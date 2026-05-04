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
use SolidInvoice\InvoiceBundle\Enum\RecurringInvoiceStatus;
use SolidInvoice\InvoiceBundle\Repository\RecurringInvoiceRepository;
use SolidInvoice\McpBundle\Mcp\Attribute\McpScopeRequired;
use SolidInvoice\McpBundle\Mcp\McpScopeGuard;
use SolidInvoice\McpBundle\Mcp\Tool\EntityNormalizer;
use SolidInvoice\McpBundle\Mcp\Tool\UlidParser;
use SolidInvoice\McpBundle\Security\McpScope;
use Symfony\Bridge\Doctrine\Types\UlidType;

final class RecurringInvoiceReadTools
{
    public function __construct(
        private readonly RecurringInvoiceRepository $recurringInvoiceRepository,
        private readonly ClientRepository $clientRepository,
        private readonly EntityNormalizer $normalizer,
        private readonly McpScopeGuard $scopeGuard,
    ) {
    }

    /**
     * List recurring invoices filtered by status, optionally scoped to a client.
     *
     * @param string|null $status    One of: new, active, complete, draft, paused, cancelled, archived (optional)
     * @param string|null $client_id Optional client ULID
     * @param int         $limit     Max rows (1..100)
     *
     * @return array{results: list<array<string, mixed>>, count: int}
     */
    #[McpTool(name: 'list_recurring_invoices', description: 'List recurring invoices, optionally filtered by status and/or client.')]
    #[McpScopeRequired(McpScope::Read)]
    public function listRecurringInvoices(?string $status = null, ?string $client_id = null, int $limit = 25): array
    {
        $this->scopeGuard->require(McpScope::Read);
        $limit = max(1, min($limit, 100));

        $qb = $this->recurringInvoiceRepository->createQueryBuilder('r')
            ->orderBy('r.created', 'DESC')
            ->setMaxResults($limit);

        if ($status !== null) {
            $statusEnum = RecurringInvoiceStatus::tryFrom($status);

            if ($statusEnum === null) {
                throw new ToolCallException(sprintf(
                    'Invalid status "%s". Valid values: %s.',
                    $status,
                    implode(', ', array_map(static fn (RecurringInvoiceStatus $s): string => $s->value, RecurringInvoiceStatus::cases())),
                ));
            }

            $qb->andWhere('r.status = :status')->setParameter('status', $statusEnum);
        }

        if ($client_id !== null) {
            $client = $this->clientRepository->find(UlidParser::parse($client_id, 'client_id'));

            if (! $client instanceof Client) {
                throw new ToolCallException(sprintf('Client %s not found.', $client_id));
            }

            $qb->andWhere('r.client = :client')->setParameter('client', $client->getId(), UlidType::NAME);
        }

        $results = $this->normalizer->normalizeMany($qb->getQuery()->getResult());

        return ['results' => $results, 'count' => count($results)];
    }
}
