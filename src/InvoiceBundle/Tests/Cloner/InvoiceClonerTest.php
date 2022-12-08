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

namespace SolidInvoice\InvoiceBundle\Tests\Cloner;

use DateTime;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\InvoiceBundle\Cloner\InvoiceCloner;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\Item;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\InvoiceBundle\Manager\InvoiceManager;
use SolidInvoice\TaxBundle\Entity\Tax;

class InvoiceClonerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testClone(): void
    {
        $currency = new Currency('USD');

        $client = new Client();
        $client->setName('Test Client');
        $client->setWebsite('http://example.com');
        $client->setCreated(new DateTime('NOW'));

        $tax = new Tax();
        $tax->setName('VAT');
        $tax->setRate(14.00);
        $tax->setType(Tax::TYPE_INCLUSIVE);

        $item = new Item();
        $item->setTax($tax);
        $item->setDescription('Item Description');
        $item->setCreated(new DateTime('now'));
        $item->setPrice(new Money(120, $currency));
        $item->setQty(10);
        $item->setTotal(new Money((12 * 10), $currency));

        $invoice = new Invoice();
        $invoice->setBaseTotal(new Money(123, $currency));
        $discount = new Discount();
        $discount->setType(Discount::TYPE_PERCENTAGE);
        $discount->setValue(12);
        $invoice->setDiscount($discount);
        $invoice->setNotes('Notes');
        $invoice->setTax(new Money(432, $currency));
        $invoice->setTerms('Terms');
        $invoice->setTotal(new Money(987, $currency));
        $invoice->setClient($client);
        $invoice->addItem($item);

        $invoiceManager = M::mock(InvoiceManager::class);
        $invoiceManager->shouldReceive('create');

        $invoiceCloner = new InvoiceCloner($invoiceManager);

        $newInvoice = $invoiceCloner->clone($invoice);

        self::assertEquals($invoice->getTotal(), $newInvoice->getTotal());
        self::assertEquals($invoice->getBaseTotal(), $newInvoice->getBaseTotal());
        self::assertSame($invoice->getDiscount(), $newInvoice->getDiscount());
        self::assertSame($invoice->getNotes(), $newInvoice->getNotes());
        self::assertSame($invoice->getTerms(), $newInvoice->getTerms());
        self::assertEquals($invoice->getTax(), $newInvoice->getTax());
        self::assertSame($client, $newInvoice->getClient());
        self::assertNull($newInvoice->getStatus());

        self::assertNotSame($invoice->getUuid(), $newInvoice->getUuid());
        self::assertNull($newInvoice->getId());

        self::assertCount(1, $newInvoice->getItems());

        $invoiceItem = $newInvoice->getItems();
        self::assertInstanceOf(Item::class, $invoiceItem[0]);

        self::assertSame($item->getTax(), $invoiceItem[0]->getTax());
        self::assertSame($item->getDescription(), $invoiceItem[0]->getDescription());
        self::assertInstanceOf(DateTime::class, $invoiceItem[0]->getCreated());
        self::assertEquals($item->getPrice(), $invoiceItem[0]->getPrice());
        self::assertSame($item->getQty(), $invoiceItem[0]->getQty());
    }

    public function testCloneWithRecurring(): void
    {
        $currency = new Currency('USD');
        $date = new \DateTime('now');

        $client = new Client();
        $client->setName('Test Client');
        $client->setWebsite('http://example.com');
        $client->setCreated(new DateTime('NOW'));

        $tax = new Tax();
        $tax->setName('VAT');
        $tax->setRate(14.00);
        $tax->setType(Tax::TYPE_INCLUSIVE);

        $item = new Item();
        $item->setTax($tax);
        $item->setDescription('Item Description');
        $item->setCreated(new DateTime('now'));
        $item->setPrice(new Money(120, $currency));
        $item->setQty(10);
        $item->setTotal(new Money((12 * 10), $currency));

        $invoice = new RecurringInvoice();
        $invoice->setBaseTotal(new Money(123, $currency));
        $discount = new Discount();
        $discount->setType(Discount::TYPE_PERCENTAGE);
        $discount->setValue(12);
        $invoice->setDiscount($discount);
        $invoice->setNotes('Notes');
        $invoice->setTax(new Money(432, $currency));
        $invoice->setTerms('Terms');
        $invoice->setTotal(new Money(987, $currency));
        $invoice->setClient($client);
        $invoice->addItem($item);
        $invoice->setFrequency('* * * * *');
        $invoice->setDateStart($date);

        $invoiceManager = M::mock(InvoiceManager::class);
        $invoiceManager->shouldReceive('create');

        $invoiceCloner = new InvoiceCloner($invoiceManager);

        /** @var RecurringInvoice $newInvoice */
        $newInvoice = $invoiceCloner->clone($invoice);

        self::assertEquals($invoice->getTotal(), $newInvoice->getTotal());
        self::assertEquals($invoice->getBaseTotal(), $newInvoice->getBaseTotal());
        self::assertSame($invoice->getDiscount(), $newInvoice->getDiscount());
        self::assertSame($invoice->getNotes(), $newInvoice->getNotes());
        self::assertSame($invoice->getTerms(), $newInvoice->getTerms());
        self::assertEquals($invoice->getTax(), $newInvoice->getTax());
        self::assertSame($client, $newInvoice->getClient());
        self::assertNull($newInvoice->getStatus());

        self::assertNull($newInvoice->getId());

        self::assertCount(1, $newInvoice->getItems());

        $invoiceItem = $newInvoice->getItems();
        self::assertInstanceOf(Item::class, $invoiceItem[0]);

        self::assertSame($item->getTax(), $invoiceItem[0]->getTax());
        self::assertSame($item->getDescription(), $invoiceItem[0]->getDescription());
        self::assertInstanceOf(DateTime::class, $invoiceItem[0]->getCreated());
        self::assertEquals($item->getPrice(), $invoiceItem[0]->getPrice());
        self::assertSame($item->getQty(), $invoiceItem[0]->getQty());
        self::assertSame($newInvoice->getFrequency(), $invoice->getFrequency());
        self::assertSame($newInvoice->getDateStart(), $invoice->getDateStart());
        self::assertSame($newInvoice->getDateEnd(), $invoice->getDateEnd());
    }
}
