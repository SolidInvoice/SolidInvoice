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

namespace SolidInvoice\MoneyBundle;

use Brick\Math\BigDecimal;
use Brick\Math\BigInteger;
use Brick\Math\BigNumber;
use Brick\Math\Exception\MathException;
use InvalidArgumentException;
use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\MoneyBundle\Formatter\MoneyFormatter;
use SolidInvoice\QuoteBundle\Entity\Quote;

/**
 * @see \SolidInvoice\MoneyBundle\Tests\CalculatorTest
 */
final class Calculator
{
    /**
     * @throws MathException
     */
    public function calculateDiscount($entity): BigDecimal
    {
        if (! $entity instanceof Quote && ! $entity instanceof Invoice) {
            throw new InvalidArgumentException(sprintf('"%s" expects instance of Quote or Invoice, "%s" given.', __METHOD__, get_debug_type($entity)));
        }

        $discount = $entity->getDiscount();

        $invoiceTotal = $entity->getBaseTotal()->plus($entity->getTax());

        if (Discount::TYPE_PERCENTAGE === $discount->getType()) {
            return BigDecimal::of($this->calculatePercentage($invoiceTotal, $discount->getValue()));
        }

        return $discount->getValue()->toBigDecimal();
    }

    /**
     * @throws MathException
     */
    public function calculatePercentage(BigInteger|int|float|string $amount, float $percentage = 0.0): float
    {
        if ($percentage < 0) {
            $percentage *= 100;
        }

        return MoneyFormatter::toFloat(BigNumber::of($amount)->toBigDecimal()->multipliedBy($percentage));
    }
}
