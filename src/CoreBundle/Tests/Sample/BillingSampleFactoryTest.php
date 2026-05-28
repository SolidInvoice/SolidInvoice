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

namespace SolidInvoice\CoreBundle\Tests\Sample;

use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Sample\BillingSampleFactory;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\QuoteBundle\Entity\Quote;

final class BillingSampleFactoryTest extends TestCase
{
    public function testInvoiceSampleIsFullyPopulated(): void
    {
        $invoice = (new BillingSampleFactory())->createInvoice();

        self::assertInstanceOf(Invoice::class, $invoice);
        self::assertSame('INV-PREVIEW-0001', $invoice->getInvoiceId());
        self::assertNotNull($invoice->getClient());
        self::assertSame('Sample Client Inc', $invoice->getClient()->getName());
        self::assertSame('USD', $invoice->getClient()->getCurrency()->getCode());
        self::assertCount(2, $invoice->getLines());
    }

    public function testQuoteSampleIsFullyPopulated(): void
    {
        $quote = (new BillingSampleFactory())->createQuote();

        self::assertInstanceOf(Quote::class, $quote);
        self::assertSame('QUOT-PREVIEW-0001', $quote->getQuoteId());
        self::assertNotNull($quote->getClient());
        self::assertSame('Sample Client Inc', $quote->getClient()->getName());
        self::assertSame('USD', $quote->getClient()->getCurrency()->getCode());
        self::assertCount(2, $quote->getLines());
    }
}
