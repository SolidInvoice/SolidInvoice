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
use Mcp\Schema\ToolAnnotations;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Repository\ClientRepository;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\InvoiceBundle\Enum\RecurringInvoiceStatus;
use SolidInvoice\InvoiceBundle\Repository\RecurringInvoiceRepository;
use SolidInvoice\McpBundle\Mcp\Attribute\McpScopeRequired;
use SolidInvoice\McpBundle\Mcp\McpScopeGuard;
use SolidInvoice\McpBundle\Mcp\Output\Entity\RecurringInvoiceOutput;
use SolidInvoice\McpBundle\Mcp\Output\ListOutput;
use SolidInvoice\McpBundle\Mcp\Output\McpOutput;
use SolidInvoice\McpBundle\Mcp\Tool\EntityNormalizer;
use SolidInvoice\McpBundle\Mcp\Tool\Paginator;
use SolidInvoice\McpBundle\Mcp\Tool\UlidParser;
use SolidInvoice\McpBundle\Security\McpScope;
use Symfony\Bridge\Doctrine\Types\UlidType;

final readonly class RecurringInvoiceReadTools
{
    public function __construct(
        private RecurringInvoiceRepository $recurringInvoiceRepository,
        private ClientRepository $clientRepository,
        private EntityNormalizer $normalizer,
        private Paginator $paginator,
        private McpScopeGuard $scopeGuard,
    ) {
    }

    /**
     * Fetch a single recurring invoice by its ID.
     *
     * @param string $id Recurring invoice ULID
     */
    #[McpTool(name: 'get_recurring_invoice', title: 'Get recurring invoice', description: 'Fetch a single recurring invoice by ID, with its schedule, lines and totals.', annotations: new ToolAnnotations(readOnlyHint: true, openWorldHint: false))]
    #[McpScopeRequired(McpScope::Read)]
    #[McpOutput(RecurringInvoiceOutput::class)]
    public function getRecurringInvoice(string $id): RecurringInvoiceOutput
    {
        $this->scopeGuard->require(McpScope::Read);

        $invoice = $this->recurringInvoiceRepository->find(UlidParser::parse($id));

        if (! $invoice instanceof RecurringInvoice) {
            throw new ToolCallException(sprintf('Recurring invoice %s not found.', $id));
        }

        return $this->normalizer->recurringInvoice($invoice);
    }

    /**
     * List recurring invoices, newest first, optionally filtered by status and/or client.
     *
     * @param string|null $status         One of: new, active, complete, draft, paused, cancelled, archived (optional)
     * @param string|null $client_id      Client ULID (optional)
     * @param int         $page           1-indexed page number
     * @param int         $items_per_page Results per page (1..100)
     */
    #[McpTool(name: 'list_recurring_invoices', title: 'List recurring invoices', description: 'List recurring invoices with pagination, optionally filtered by status and/or client.', annotations: new ToolAnnotations(readOnlyHint: true, openWorldHint: false))]
    #[McpScopeRequired(McpScope::Read)]
    #[McpOutput(RecurringInvoiceOutput::class, list: true)]
    public function listRecurringInvoices(?string $status = null, ?string $client_id = null, int $page = 1, int $items_per_page = 25): ListOutput
    {
        $this->scopeGuard->require(McpScope::Read);

        $qb = $this->recurringInvoiceRepository->createQueryBuilder('r')->orderBy('r.created', 'DESC');

        if ($status !== null) {
            $statusEnum = RecurringInvoiceStatus::tryFrom($status) ?? throw new ToolCallException(sprintf(
                'Invalid status "%s". Valid values: %s.',
                $status,
                implode(', ', array_column(RecurringInvoiceStatus::cases(), 'value')),
            ));

            $qb->andWhere('r.status = :status')->setParameter('status', $statusEnum);
        }

        if ($client_id !== null) {
            $client = $this->clientRepository->find(UlidParser::parse($client_id, 'client_id'));

            if (! $client instanceof Client) {
                throw new ToolCallException(sprintf('Client %s not found.', $client_id));
            }

            $qb->andWhere('r.client = :client')->setParameter('client', $client->getId(), UlidType::NAME);
        }

        return $this->paginator->paginate($qb, $page, $items_per_page);
    }
}
