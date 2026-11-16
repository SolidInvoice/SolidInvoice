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

namespace SolidInvoice\InvoiceBundle\Tests\Entity;

use LogicException;
use Money\Currency;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\InvoiceBundle\Entity\Invoice;

/**
 * @see \SolidInvoice\InvoiceBundle\Entity\BaseInvoice::getCurrency()
 */
#[CoversClass(Invoice::class)]
final class InvoiceCurrencyTest extends TestCase
{
    public function testGetCurrencyReturnsFrozenValueWhenSet(): void
    {
        $client = new Client();
        $client->setCurrency(new Currency('EUR'));

        $invoice = new Invoice();
        $invoice->setClient($client);
        $invoice->setCurrencyCode('USD');

        self::assertSame('USD', $invoice->getCurrency()->getCode());
    }

    public function testGetCurrencyFallsBackToClientWhenNotFrozen(): void
    {
        $client = new Client();
        $client->setCurrency(new Currency('GBP'));

        $invoice = new Invoice();
        $invoice->setClient($client);

        self::assertSame('GBP', $invoice->getCurrency()->getCode());
    }

    public function testGetCurrencyThrowsWithoutAClient(): void
    {
        $invoice = new Invoice();

        $this->expectException(LogicException::class);

        $invoice->getCurrency();
    }
}
