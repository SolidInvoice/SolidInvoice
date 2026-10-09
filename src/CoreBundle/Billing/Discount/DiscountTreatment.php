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
use SolidInvoice\QuoteBundle\Entity\Quote;

/**
 * How a document's discount is turned into money, selected by the
 * {@see \SolidInvoice\CoreBundle\Enum\TaxArithmeticVersion} the document is pinned to.
 */
interface DiscountTreatment
{
    /**
     * The document-level discount, in whole minor units.
     *
     * @throws MathException
     */
    public function amount(
        BaseInvoice | Quote $document,
        BigDecimal $subTotal,
        BigDecimal $totalLineTax,
    ): BigDecimal;

    /**
     * How much of $amount each line's taxable base loses. Sums to exactly $amount.
     *
     * @param list<BigDecimal> $lineSubtotals
     *
     * @return list<BigDecimal>
     *
     * @throws MathException
     */
    public function allocate(BigDecimal $amount, array $lineSubtotals): array;
}
