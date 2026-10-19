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

namespace SolidInvoice\CoreBundle\Billing\Discount;

use Brick\Math\BigDecimal;
use Brick\Math\Exception\MathException;
use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\InvoiceBundle\Entity\BaseInvoice;
use SolidInvoice\MoneyBundle\Calculator;
use SolidInvoice\QuoteBundle\Entity\Quote;
use function array_fill;
use function count;

/**
 * {@see \SolidInvoice\CoreBundle\Enum\TaxArithmeticVersion::Legacy} — behaviour-preserving,
 * must not be "tidied". It reproduces today's arithmetic exactly, including the
 * order-dependency that comes with it: {@see \SolidInvoice\CoreBundle\Billing\TotalCalculator}
 * resolves this treatment while computing a fresh pass, before the document's own
 * `tax` field has been overwritten for that pass. This mirrors that exactly rather
 * than calling {@see Calculator::calculateDiscount()}, which reads the document's
 * {@see BaseInvoice::getBaseTotal()}/{@see Quote::getBaseTotal()} directly — a value
 * this treatment is resolved before the document's own base total is updated for the
 * same pass, so reading it here would use the previous pass's figure instead of this
 * one's $subTotal.
 */
final readonly class LegacyDiscountTreatment implements DiscountTreatment
{
    public function __construct(
        private Calculator $calculator,
    ) {
    }

    /**
     * @throws MathException
     */
    public function amount(BaseInvoice | Quote $document, BigDecimal $subTotal, BigDecimal $totalLineTax): BigDecimal
    {
        $discount = $document->getDiscount();

        if (Discount::TYPE_PERCENTAGE === $discount->getType()) {
            $invoiceTotal = $subTotal->plus($document->getTax());

            return BigDecimal::of((string) $this->calculator->calculatePercentage($invoiceTotal, $discount->getValue()));
        }

        return $discount->getValueMoney()->toBigDecimal();
    }

    public function allocate(BigDecimal $amount, array $lineSubtotals): array
    {
        return array_fill(0, count($lineSubtotals), BigDecimal::zero());
    }
}
