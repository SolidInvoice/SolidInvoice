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

use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Model\Graph as InvoiceGraph;
use SolidInvoice\InvoiceBundle\Repository\InvoiceRepository;
use SolidInvoice\McpBundle\Mcp\Attribute\McpScopeRequired;
use SolidInvoice\McpBundle\Mcp\McpScopeGuard;
use SolidInvoice\McpBundle\Mcp\Tool\EntityNormalizer;
use SolidInvoice\McpBundle\Mcp\Tool\UlidParser;
use SolidInvoice\McpBundle\Security\McpScope;
use SolidInvoice\PaymentBundle\Entity\Payment;
use SolidInvoice\PaymentBundle\Entity\PaymentMethod;
use SolidInvoice\PaymentBundle\Enum\PaymentStatus;
use SolidInvoice\PaymentBundle\Repository\PaymentMethodRepository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Workflow\WorkflowInterface;

final class PaymentWriteTools
{
    public function __construct(
        private readonly InvoiceRepository $invoiceRepository,
        private readonly PaymentMethodRepository $paymentMethodRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly EntityNormalizer $normalizer,
        #[Autowire(service: 'state_machine.invoice')]
        private readonly WorkflowInterface $invoiceWorkflow,
        private readonly McpScopeGuard $scopeGuard,
    ) {
    }

    /**
     * Record a payment against an invoice. Uses the offline payment gateway.
     * Auto-transitions the invoice to "paid" when the balance is covered.
     *
     * @param string      $invoice_id Invoice ULID
     * @param int         $amount     Amount in minor units (e.g. cents)
     * @param string      $currency   ISO 4217 currency code (must match the invoice's currency)
     * @param string|null $reference  Optional reference (e.g. bank transaction ID)
     * @param string|null $notes      Optional notes
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'record_payment', description: 'Record an offline payment against an invoice and transition it to paid.')]
    #[McpScopeRequired(McpScope::Write)]
    public function recordPayment(
        string $invoice_id,
        int $amount,
        string $currency,
        ?string $reference = null,
        ?string $notes = null,
    ): array {
        $this->scopeGuard->require(McpScope::Write);

        if ($amount <= 0) {
            throw new ToolCallException('amount must be greater than zero.');
        }

        $invoice = $this->invoiceRepository->find(UlidParser::parse($invoice_id, 'invoice_id'));

        if (! $invoice instanceof Invoice) {
            throw new ToolCallException(sprintf('Invoice %s not found.', $invoice_id));
        }

        $client = $invoice->getClient();
        $invoiceCurrency = $client?->getCurrencyCode();

        if ($invoiceCurrency === null) {
            throw new ToolCallException('Invoice has no resolvable currency.');
        }

        if (strtoupper($currency) !== strtoupper($invoiceCurrency)) {
            throw new ToolCallException(sprintf(
                'Payment currency "%s" does not match invoice currency "%s".',
                $currency,
                $invoiceCurrency,
            ));
        }

        // Reject overpayment so the MCP tool matches the behaviour of the
        // Prepare action (src/PaymentBundle/Action/Prepare.php).
        $balance = $invoice->getBalance();

        if ($balance->isLessThan($amount)) {
            throw new ToolCallException(sprintf(
                'Payment amount %d exceeds invoice balance %s.',
                $amount,
                $balance->__toString(),
            ));
        }

        if (! $this->invoiceWorkflow->can($invoice, InvoiceGraph::TRANSITION_PAY)) {
            throw new ToolCallException(sprintf(
                '"pay" transition cannot be applied to invoice in status "%s".',
                $invoice->getStatus()?->value ?? 'unknown',
            ));
        }

        $offlineMethod = $this->paymentMethodRepository->findOneBy(['factoryName' => PaymentMethod::FACTORY_OFFLINE]);

        if (! $offlineMethod instanceof PaymentMethod) {
            throw new ToolCallException('Offline payment method is not configured for this company.');
        }

        $payment = new Payment();
        $payment->setTotalAmount($amount);
        $payment->setCurrencyCode(strtoupper($currency));
        $payment->setReference($reference);
        $payment->setNotes($notes);
        $payment->setMethod($offlineMethod);
        $payment->setInvoice($invoice);

        if ($client !== null) {
            $payment->setClient($client);
        }

        $payment->setStatus(PaymentStatus::Captured);
        $payment->setCompleted(new DateTimeImmutable());
        // Company is bound from the invoice; never accept client-supplied company.
        $payment->setCompany($invoice->getCompany());

        $this->entityManager->persist($payment);
        $this->invoiceWorkflow->apply($invoice, InvoiceGraph::TRANSITION_PAY);
        $this->entityManager->flush();

        return $this->normalizer->normalize($payment);
    }
}
