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
use SolidInvoice\InvoiceBundle\Entity\BaseInvoice;
use SolidInvoice\MoneyBundle\Calculator;
use SolidInvoice\QuoteBundle\Entity\Quote;
use function array_map;

/**
 * {@see \SolidInvoice\CoreBundle\Enum\TaxArithmeticVersion::Legacy} — behaviour-preserving,
 * must not be "tidied". It reproduces today's arithmetic exactly, including reading the
 * document's persisted totals rather than the pass-1 figures, and the order-dependency that
 * comes with it.
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
        return $this->calculator->calculateDiscount($document)->toBigDecimal();
    }

    public function allocate(BigDecimal $amount, array $lineSubtotals): array
    {
        return array_map(BigDecimal::zero(...), $lineSubtotals);
    }
}
