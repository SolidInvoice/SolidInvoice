<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\InvoiceBundle\Tests\Cloner;

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
use SolidInvoice\InvoiceBundle\Manager\InvoiceManager;
use SolidInvoice\TaxBundle\Entity\Tax;

class InvoiceClonerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testClone()
    {
        $currency = new Currency('USD');

        $client = new Client();
        $client->setName('Test Client');
        $client->setWebsite('http://example.com');
        $client->setCreated(new \DateTime('NOW'));

        $tax = new Tax();
        $tax->setName('VAT');
        $tax->setRate(14.00);
        $tax->setType(Tax::TYPE_INCLUSIVE);

        $item = new Item();
        $item->setTax($tax);
        $item->setDescription('Item Description');
        $item->setCreated(new \DateTime('now'));
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

        $this->assertEquals($invoice->getTotal(), $newInvoice->getTotal());
        $this->assertEquals($invoice->getBaseTotal(), $newInvoice->getBaseTotal());
        $this->assertSame($invoice->getDiscount(), $newInvoice->getDiscount());
        $this->assertSame($invoice->getNotes(), $newInvoice->getNotes());
        $this->assertSame($invoice->getTerms(), $newInvoice->getTerms());
        $this->assertEquals($invoice->getTax(), $newInvoice->getTax());
        $this->assertSame($client, $newInvoice->getClient());
        $this->assertNull($newInvoice->getStatus());

        $this->assertNotSame($invoice->getUuid(), $newInvoice->getUuid());
        $this->assertNull($newInvoice->getId());

        $this->assertCount(1, $newInvoice->getItems());

        $invoiceItem = $newInvoice->getItems();
        $this->assertInstanceOf(Item::class, $invoiceItem[0]);

        $this->assertSame($item->getTax(), $invoiceItem[0]->getTax());
        $this->assertSame($item->getDescription(), $invoiceItem[0]->getDescription());
        $this->assertInstanceOf(\DateTime::class, $invoiceItem[0]->getCreated());
        $this->assertEquals($item->getPrice(), $invoiceItem[0]->getPrice());
        $this->assertSame($item->getQty(), $invoiceItem[0]->getQty());
    }
}
