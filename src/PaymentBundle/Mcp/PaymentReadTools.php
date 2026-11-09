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

namespace SolidInvoice\PaymentBundle\Mcp;

use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\ToolAnnotations;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Repository\ClientRepository;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Repository\InvoiceRepository;
use SolidInvoice\McpBundle\Mcp\Attribute\McpScopeRequired;
use SolidInvoice\McpBundle\Mcp\McpScopeGuard;
use SolidInvoice\McpBundle\Mcp\Output\Entity\PaymentOutput;
use SolidInvoice\McpBundle\Mcp\Output\ListOutput;
use SolidInvoice\McpBundle\Mcp\Output\McpOutput;
use SolidInvoice\McpBundle\Mcp\Tool\EntityNormalizer;
use SolidInvoice\McpBundle\Mcp\Tool\Paginator;
use SolidInvoice\McpBundle\Mcp\Tool\UlidParser;
use SolidInvoice\McpBundle\Security\McpScope;
use SolidInvoice\PaymentBundle\Entity\Payment;
use SolidInvoice\PaymentBundle\Repository\PaymentRepository;
use Symfony\Bridge\Doctrine\Types\UlidType;

final readonly class PaymentReadTools
{
    public function __construct(
        private PaymentRepository $paymentRepository,
        private InvoiceRepository $invoiceRepository,
        private ClientRepository $clientRepository,
        private EntityNormalizer $normalizer,
        private Paginator $paginator,
        private McpScopeGuard $scopeGuard,
    ) {
    }

    /**
     * Fetch a single payment by its ID.
     *
     * @param string $id Payment ULID
     */
    #[McpTool(name: 'get_payment', title: 'Get payment', description: 'Fetch a single payment by ID.', annotations: new ToolAnnotations(readOnlyHint: true, openWorldHint: false))]
    #[McpScopeRequired(McpScope::Read)]
    #[McpOutput(PaymentOutput::class)]
    public function getPayment(string $id): PaymentOutput
    {
        $this->scopeGuard->require(McpScope::Read);

        $payment = $this->paymentRepository->find(UlidParser::parse($id));

        if (! $payment instanceof Payment) {
            throw new ToolCallException(sprintf('Payment %s not found.', $id));
        }

        return $this->normalizer->payment($payment);
    }

    /**
     * List payments, newest first, optionally for one invoice and/or client.
     *
     * @param string|null $invoice_id     Invoice ULID (optional)
     * @param string|null $client_id      Client ULID (optional)
     * @param int         $page           1-indexed page number
     * @param int         $items_per_page Results per page (1..100)
     */
    #[McpTool(name: 'list_payments', title: 'List payments', description: 'List payments with pagination, optionally for a specific invoice and/or client.', annotations: new ToolAnnotations(readOnlyHint: true, openWorldHint: false))]
    #[McpScopeRequired(McpScope::Read)]
    #[McpOutput(PaymentOutput::class, list: true)]
    public function listPayments(?string $invoice_id = null, ?string $client_id = null, int $page = 1, int $items_per_page = 25): ListOutput
    {
        $this->scopeGuard->require(McpScope::Read);

        $qb = $this->paymentRepository->createQueryBuilder('p')->orderBy('p.created', 'DESC');

        if ($invoice_id !== null) {
            $invoice = $this->invoiceRepository->find(UlidParser::parse($invoice_id, 'invoice_id'));

            if (! $invoice instanceof Invoice) {
                throw new ToolCallException(sprintf('Invoice %s not found.', $invoice_id));
            }

            $qb->andWhere('p.invoice = :invoice')->setParameter('invoice', $invoice->getId(), UlidType::NAME);
        }

        if ($client_id !== null) {
            $client = $this->clientRepository->find(UlidParser::parse($client_id, 'client_id'));

            if (! $client instanceof Client) {
                throw new ToolCallException(sprintf('Client %s not found.', $client_id));
            }

            $qb->andWhere('p.client = :client')->setParameter('client', $client->getId(), UlidType::NAME);
        }

        return $this->paginator->paginate($qb, $page, $items_per_page);
    }
}
