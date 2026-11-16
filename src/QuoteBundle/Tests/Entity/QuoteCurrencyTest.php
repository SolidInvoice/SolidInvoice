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

namespace SolidInvoice\QuoteBundle\Tests\Entity;

use LogicException;
use Money\Currency;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\QuoteBundle\Entity\Quote;

/**
 * @see \SolidInvoice\QuoteBundle\Entity\Quote::getCurrency()
 */
#[CoversClass(Quote::class)]
final class QuoteCurrencyTest extends TestCase
{
    public function testGetCurrencyReturnsFrozenValueWhenSet(): void
    {
        $client = new Client();
        $client->setCurrency(new Currency('EUR'));

        $quote = new Quote();
        $quote->setClient($client);
        $quote->setCurrencyCode('USD');

        self::assertSame('USD', $quote->getCurrency()->getCode());
    }

    public function testGetCurrencyFallsBackToClientWhenNotFrozen(): void
    {
        $client = new Client();
        $client->setCurrency(new Currency('GBP'));

        $quote = new Quote();
        $quote->setClient($client);

        self::assertSame('GBP', $quote->getCurrency()->getCode());
    }

    public function testGetCurrencyThrowsWithoutAClient(): void
    {
        $quote = new Quote();

        $this->expectException(LogicException::class);

        $quote->getCurrency();
    }
}
