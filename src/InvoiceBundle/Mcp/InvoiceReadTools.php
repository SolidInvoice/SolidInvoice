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
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Repository\InvoiceRepository;
use SolidInvoice\McpBundle\Mcp\Attribute\McpScopeRequired;
use SolidInvoice\McpBundle\Mcp\McpScopeGuard;
use SolidInvoice\McpBundle\Mcp\Output\Entity\InvoiceOutput;
use SolidInvoice\McpBundle\Mcp\Output\ListOutput;
use SolidInvoice\McpBundle\Mcp\Output\McpOutput;
use SolidInvoice\McpBundle\Mcp\Tool\EntityNormalizer;
use SolidInvoice\McpBundle\Mcp\Tool\Paginator;
use SolidInvoice\McpBundle\Mcp\Tool\UlidParser;
use SolidInvoice\McpBundle\Security\McpScope;
use Symfony\Bridge\Doctrine\Types\UlidType;

final readonly class InvoiceReadTools
{
    public function __construct(
        private InvoiceRepository $invoiceRepository,
        private ClientRepository $clientRepository,
        private EntityNormalizer $normalizer,
        private Paginator $paginator,
        private McpScopeGuard $scopeGuard,
    ) {
    }

    /**
     * Fetch a single invoice by its ID.
     *
     * @param string $id Invoice ULID
     */
    #[McpTool(name: 'get_invoice', title: 'Get invoice', description: 'Fetch a single invoice by ID, with its lines, taxes and totals.', annotations: new ToolAnnotations(readOnlyHint: true, openWorldHint: false))]
    #[McpScopeRequired(McpScope::Read)]
    #[McpOutput(InvoiceOutput::class)]
    public function getInvoice(string $id): InvoiceOutput
    {
        $this->scopeGuard->require(McpScope::Read);

        $invoice = $this->invoiceRepository->find(UlidParser::parse($id));

        if (! $invoice instanceof Invoice) {
            throw new ToolCallException(sprintf('Invoice %s not found.', $id));
        }

        return $this->normalizer->invoice($invoice);
    }

    /**
     * List invoices, newest first, optionally filtered by status and/or client. With status
     * "overdue" the list is ordered by due date, the longest overdue first.
     *
     * @param string|null $status         One of: new, draft, pending, paid, overdue, cancelled, archived (optional)
     * @param string|null $client_id      Client ULID (optional)
     * @param int         $page           1-indexed page number
     * @param int         $items_per_page Results per page (1..100)
     */
    #[McpTool(name: 'list_invoices', title: 'List invoices', description: 'List invoices with pagination, optionally filtered by status (e.g. overdue) and/or client.', annotations: new ToolAnnotations(readOnlyHint: true, openWorldHint: false))]
    #[McpScopeRequired(McpScope::Read)]
    #[McpOutput(InvoiceOutput::class, list: true)]
    public function listInvoices(?string $status = null, ?string $client_id = null, int $page = 1, int $items_per_page = 25): ListOutput
    {
        $this->scopeGuard->require(McpScope::Read);

        $qb = $this->invoiceRepository->createQueryBuilder('i');

        if ($status !== null) {
            $statusEnum = InvoiceStatus::tryFrom($status) ?? throw new ToolCallException(sprintf(
                'Invalid status "%s". Valid values: %s.',
                $status,
                implode(', ', array_column(InvoiceStatus::cases(), 'value')),
            ));

            $qb->andWhere('i.status = :status')->setParameter('status', $statusEnum);
        }

        if ($status === InvoiceStatus::Overdue->value) {
            $qb->orderBy('i.due', 'ASC');
        } else {
            $qb->orderBy('i.created', 'DESC');
        }

        if ($client_id !== null) {
            $client = $this->clientRepository->find(UlidParser::parse($client_id, 'client_id'));

            if (! $client instanceof Client) {
                throw new ToolCallException(sprintf('Client %s not found.', $client_id));
            }

            $qb->andWhere('i.client = :client')->setParameter('client', $client->getId(), UlidType::NAME);
        }

        return $this->paginator->paginate($qb, $page, $items_per_page);
    }
}
