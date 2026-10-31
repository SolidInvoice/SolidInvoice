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

namespace SolidInvoice\PaymentBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SolidInvoice\PaymentBundle\Entity\Payment;

final class PaymentTest extends TestCase
{
    /**
     * @return iterable<string, array{string, int, float}>
     */
    public static function searchableTotalProvider(): iterable
    {
        yield 'two decimals' => ['USD', 12345, 123.45];
        yield 'no decimals' => ['JPY', 5000, 5000.0];
        yield 'three decimals' => ['BHD', 12345, 12.345];
    }

    #[DataProvider('searchableTotalProvider')]
    public function testTheSearchableTotalIsInTheCurrencysMajorUnit(string $currency, int $amount, float $expected): void
    {
        $payment = new Payment();
        $payment->setCurrencyCode($currency);
        $payment->setTotalAmount($amount);

        self::assertSame($expected, $payment->getSearchableTotal());
    }
}
