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

use Brick\Math\BigInteger;
use Carbon\CarbonImmutable;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Repository\InvoiceRepository;
use SolidInvoice\McpBundle\Mcp\Attribute\McpScopeRequired;
use SolidInvoice\McpBundle\Mcp\McpScopeGuard;
use SolidInvoice\McpBundle\Mcp\Tool\EntityNormalizer;
use SolidInvoice\McpBundle\Mcp\Tool\UlidParser;
use SolidInvoice\McpBundle\Security\McpScope;
use SolidInvoice\PaymentBundle\Exception\InvalidPaymentException;
use SolidInvoice\PaymentBundle\Exception\PaymentConflictException;
use SolidInvoice\PaymentBundle\Manager\ManualPaymentManager;
use Throwable;

final readonly class PaymentWriteTools
{
    public function __construct(
        private InvoiceRepository $invoiceRepository,
        private ManualPaymentManager $paymentManager,
        private EntityNormalizer $normalizer,
        private McpScopeGuard $scopeGuard,
    ) {
    }

    /**
     * Record a payment received outside SolidInvoice against an invoice, as the web payment form
     * does: the invoice is marked paid once its balance is settled, and an overpayment is added to
     * the client's credit.
     *
     * @param string      $invoice_id Invoice ULID
     * @param int         $amount     Amount in minor units (e.g. cents)
     * @param string      $currency   ISO 4217 currency code (must match the invoice's currency)
     * @param string|null $reference  Optional reference (e.g. bank transaction ID)
     * @param string|null $notes      Optional notes
     * @param string|null $method     Gateway name of an enabled offline payment method (e.g. cash, bank_transfer, credit); cash when omitted
     * @param string|null $paid_at    Date the payment was received (YYYY-MM-DD); today when omitted
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'record_payment', description: 'Record an offline payment against an invoice; the invoice transitions to paid once its balance is settled, and any overpayment becomes client credit.')]
    #[McpScopeRequired(McpScope::Write)]
    public function recordPayment(
        string $invoice_id,
        int $amount,
        string $currency,
        ?string $reference = null,
        ?string $notes = null,
        ?string $method = null,
        ?string $paid_at = null,
    ): array {
        $this->scopeGuard->require(McpScope::Write);

        $invoice = $this->invoiceRepository->find(UlidParser::parse($invoice_id, 'invoice_id'));

        if (! $invoice instanceof Invoice) {
            throw new ToolCallException(sprintf('Invoice %s not found.', $invoice_id));
        }

        try {
            $paidAt = $paid_at === null ? null : new CarbonImmutable($paid_at, 'UTC');
        } catch (Throwable) {
            throw new ToolCallException(sprintf('paid_at "%s" is not a date (YYYY-MM-DD).', $paid_at));
        }

        try {
            $payment = $this->paymentManager->record($invoice, BigInteger::of($amount), $currency, $method, $paidAt, $reference, $notes);
        } catch (InvalidPaymentException $exception) {
            throw new ToolCallException(sprintf('%s: %s', $exception->field, $exception->getMessage()), $exception->getCode(), $exception);
        } catch (PaymentConflictException $exception) {
            throw new ToolCallException($exception->getMessage(), $exception->getCode(), $exception);
        }

        return [
            ...$this->normalizer->normalize($payment),
            'credit_added' => (string) $payment->getCreditAdded(),
        ];
    }
}
