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
use Brick\Math\BigNumber;
use Brick\Math\Exception\MathException;
use Brick\Math\RoundingMode;
use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\InvoiceBundle\Entity\BaseInvoice;
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
    public function calculateDiscount(Quote|BaseInvoice $entity): BigNumber
    {
        $discount = $entity->getDiscount();

        $invoiceTotal = $entity->getBaseTotal()->toBigDecimal()->plus($entity->getTax());

        if (Discount::TYPE_PERCENTAGE === $discount->getType()) {
            return BigDecimal::of((string) $this->calculatePercentage($invoiceTotal, $discount->getValue()));
        }

        return $discount->getValueMoney();
    }

    /**
     * @throws MathException
     */
    public function calculatePercentage(BigNumber|int|string $amount, float $percentage = 0.0): float
    {
        if ($percentage > 100) {
            $percentage /= 100;
        }

        return MoneyFormatter::toFloat(BigNumber::of($amount)->toBigDecimal()->multipliedBy(BigDecimal::of((string) $percentage)->dividedBy(100, 10, RoundingMode::HalfEven)));
    }
}
