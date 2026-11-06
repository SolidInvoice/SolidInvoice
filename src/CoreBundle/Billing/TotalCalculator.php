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

namespace SolidInvoice\CoreBundle\Billing;

use Brick\Math\BigDecimal;
use Brick\Math\BigInteger;
use Brick\Math\BigNumber;
use Brick\Math\Exception\MathException;
use SolidInvoice\InvoiceBundle\Entity\BaseInvoice;
use SolidInvoice\InvoiceBundle\Entity\CreditNote;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Repository\CreditNoteRepository;
use SolidInvoice\MoneyBundle\Calculator;
use SolidInvoice\PaymentBundle\Repository\PaymentRepository;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\TaxBundle\Calculator\TaxCalculatorInterface;

/**
 * Populates {@see BaseInvoice}/{@see Quote} totals (subtotal, tax, grand total,
 * balance) by delegating tax math to {@see TaxCalculatorInterface} and discount math
 * to {@see Calculator::calculateDiscount()}.
 *
 * @see \SolidInvoice\CoreBundle\Tests\Billing\TotalCalculatorTest
 */
class TotalCalculator
{
    public function __construct(
        private readonly PaymentRepository $paymentRepository,
        private readonly Calculator $calculator,
        private readonly TaxCalculatorInterface $taxCalculator,
        private readonly CreditNoteRepository $creditNoteRepository,
    ) {
    }

    /**
     * @throws MathException
     */
    public function calculateTotals(BaseInvoice | Quote $entity): void
    {
        $this->updateTotal($entity);

        if ($entity instanceof Invoice) {
            $entity->setBalance($this->calculateBalance($entity));
        }
    }

    /**
     * The single place an invoice balance is derived: total minus payments received minus
     * issued credit notes, floored at zero. See `design` §0/§5 on SOL-69. Callers that used to
     * inline this formula (such as `PaymentCompleteListener`) must route through here instead of
     * duplicating it — otherwise paying an invoice that carries a credit note silently restores
     * the credited amount.
     *
     * @throws MathException
     */
    public function calculateBalance(Invoice $invoice): BigNumber
    {
        return $this->netBalance($invoice, $this->creditNoteRepository->getTotalCreditedForInvoice($invoice));
    }

    /**
     * The client-credit amount attributable to one specific credit note counting against its
     * invoice, isolated from every other credit note on the same invoice. Used by both
     * `CreditNoteIssuedListener` (to grant) and `CreditNoteCancelledListener` (to reverse) so that
     * sequential credit notes on the same invoice do not double-grant or double-reverse client
     * credit. See `design` §0/§6 on SOL-69.
     *
     * @throws MathException
     */
    public function calculateOverflowContribution(CreditNote $creditNote): BigNumber
    {
        $invoice = $creditNote->getInvoice();
        assert($invoice instanceof Invoice);

        $creditedByOthers = $this->creditNoteRepository->getTotalCreditedForInvoice($invoice, $creditNote);
        $creditedWithThis = $this->narrow($creditedByOthers)->plus($creditNote->getTotal());

        return $this->overflowAmount($invoice, $creditedWithThis)->minus($this->overflowAmount($invoice, $creditedByOthers));
    }

    /**
     * @throws MathException
     */
    private function netBalance(Invoice $invoice, BigNumber $credited): BigDecimal | BigInteger
    {
        $balance = $this->narrow($invoice->getTotal())
            ->minus($this->paymentRepository->getTotalPaidForInvoice($invoice))
            ->minus($credited);

        return $balance->isNegative() ? BigInteger::zero() : $balance;
    }

    /**
     * @throws MathException
     */
    private function overflowAmount(Invoice $invoice, BigNumber $credited): BigDecimal | BigInteger
    {
        $overflow = $this->narrow($this->paymentRepository->getTotalPaidForInvoice($invoice))
            ->plus($credited)
            ->minus($invoice->getTotal());

        return $overflow->isPositive() ? $overflow : BigInteger::zero();
    }

    private function narrow(BigNumber $value): BigDecimal | BigInteger
    {
        assert($value instanceof BigDecimal || $value instanceof BigInteger);

        return $value;
    }

    /**
     * @throws MathException
     */
    private function updateTotal(BaseInvoice | Quote $entity): void
    {
        $result = $this->taxCalculator->calculate($entity);

        $subTotal = $result->subTotal;
        $tax = $result->getTotalTax();
        $total = $result->total;
        $withholding = $result->totalWithholding;

        $entity->setBaseTotal($subTotal);

        if ($entity->getDiscount()->getValue()) {
            $total = $this->applyDiscount($entity, $total);
        }

        $entity->setTotal($total);
        $entity->setTax($tax);
        $entity->setWithholdingAmount($withholding);
        $entity->setPayableAmount(BigDecimal::of($total)->minus($withholding));
    }

    /**
     * @throws MathException
     */
    private function applyDiscount(BaseInvoice | Quote $entity, BigDecimal | BigInteger $total): BigNumber
    {
        return $total->minus($this->calculator->calculateDiscount($entity));
    }
}
