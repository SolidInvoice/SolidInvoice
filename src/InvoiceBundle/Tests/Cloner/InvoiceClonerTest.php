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

use Brick\Math\Exception\MathException;
use DateTime;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use PHPUnit\Framework\TestCase;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\CoreBundle\Generator\BillingIdGenerator;
use SolidInvoice\CoreBundle\Generator\BillingIdGenerator\RandomNumberGenerator;
use SolidInvoice\InvoiceBundle\Cloner\InvoiceCloner;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\Line;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoiceLine;
use SolidInvoice\InvoiceBundle\Manager\InvoiceManager;
use SolidInvoice\SettingsBundle\SystemConfig;
use SolidInvoice\TaxBundle\Entity\Tax;
use Symfony\Component\DependencyInjection\ServiceLocator;

class InvoiceClonerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @throws MathException
     */
    public function testClone(): void
    {
        $client = new Client();
        $client->setName('Test Client');
        $client->setWebsite('https://example.com');
        $client->setCreated(new DateTime('NOW'));

        $tax = new Tax();
        $tax->setName('VAT');
        $tax->setRate(14.00);
        $tax->setType(Tax::TYPE_INCLUSIVE);

        $line = new Line();
        $line->setTax($tax);
        $line->setDescription('Line Description');
        $line->setCreated(new DateTime('now'));
        $line->setPrice(120);
        $line->setQty(10);
        $line->setTotal(120 * 10);

        $invoice = new Invoice();
        $invoice->setBaseTotal(123);
        $discount = new Discount();
        $discount->setType(Discount::TYPE_PERCENTAGE);
        $discount->setValue(12);
        $invoice->setDiscount($discount);
        $invoice->setNotes('Notes');
        $invoice->setTax(432);
        $invoice->setTerms('Terms');
        $invoice->setTotal(987);
        $invoice->setClient($client);
        $invoice->addLine($line);

        $invoiceManager = M::mock(InvoiceManager::class);
        $invoiceManager->shouldReceive('create');

        $systemConfig = M::mock(SystemConfig::class);

        $systemConfig->shouldReceive('get')
            ->once()
            ->with('invoice/id_generation/strategy')
            ->andReturn('random_number');

        $systemConfig->shouldReceive('get')
            ->once()
            ->with('invoice/id_generation/prefix')
            ->andReturn('');

        $systemConfig->shouldReceive('get')
            ->once()
            ->with('invoice/id_generation/suffix')
            ->andReturn('');

        $invoiceCloner = new InvoiceCloner($invoiceManager, new BillingIdGenerator(new ServiceLocator([
            'random_number' => fn () => new RandomNumberGenerator(),
        ]), $systemConfig));

        $newInvoice = $invoiceCloner->clone($invoice);

        self::assertInstanceOf(Invoice::class, $newInvoice);

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
        self::assertNotEquals($invoice->getInvoiceId(), $newInvoice->getInvoiceId());

        self::assertCount(1, $newInvoice->getLines());

        $invoiceLine = $newInvoice->getLines();
        self::assertInstanceOf(Line::class, $invoiceLine[0]);

        self::assertSame($line->getTax(), $invoiceLine[0]->getTax());
        self::assertSame($line->getDescription(), $invoiceLine[0]->getDescription());
        self::assertInstanceOf(DateTime::class, $invoiceLine[0]->getCreated());
        self::assertEquals($line->getPrice(), $invoiceLine[0]->getPrice());
        self::assertSame($line->getQty(), $invoiceLine[0]->getQty());
    }

    public function testCloneWithRecurring(): void
    {
        $date = new DateTime('now');

        $client = new Client();
        $client->setName('Test Client');
        $client->setWebsite('http://example.com');
        $client->setCreated(new DateTime('NOW'));

        $tax = new Tax();
        $tax->setName('VAT');
        $tax->setRate(14.00);
        $tax->setType(Tax::TYPE_INCLUSIVE);

        $line = new RecurringInvoiceLine();
        $line->setTax($tax);
        $line->setDescription('Line Description');
        $line->setCreated(new DateTime('now'));
        $line->setPrice(120);
        $line->setQty(10);
        $line->setTotal(120 * 10);

        $invoice = new RecurringInvoice();
        $invoice->setBaseTotal(123);
        $discount = new Discount();
        $discount->setType(Discount::TYPE_PERCENTAGE);
        $discount->setValue(12);
        $invoice->setDiscount($discount);
        $invoice->setNotes('Notes');
        $invoice->setTax(432);
        $invoice->setTerms('Terms');
        $invoice->setTotal(987);
        $invoice->setClient($client);
        $invoice->addLine($line);
        $invoice->setFrequency('* * * * *');
        $invoice->setDateStart($date);

        $invoiceManager = M::mock(InvoiceManager::class);
        $invoiceManager->shouldReceive('create');

        $invoiceCloner = new InvoiceCloner($invoiceManager, new BillingIdGenerator(new ServiceLocator([]), $this->createMock(SystemConfig::class)));

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

        self::assertCount(1, $newInvoice->getLines());

        $invoiceLine = $newInvoice->getLines();
        self::assertInstanceOf(Line::class, $invoiceLine[0]);

        self::assertSame($line->getTax(), $invoiceLine[0]->getTax());
        self::assertSame($line->getDescription(), $invoiceLine[0]->getDescription());
        self::assertInstanceOf(DateTime::class, $invoiceLine[0]->getCreated());
        self::assertEquals($line->getPrice(), $invoiceLine[0]->getPrice());
        self::assertSame($line->getQty(), $invoiceLine[0]->getQty());
        self::assertSame($newInvoice->getFrequency(), $invoice->getFrequency());
        self::assertSame($newInvoice->getDateStart(), $invoice->getDateStart());
        self::assertSame($newInvoice->getDateEnd(), $invoice->getDateEnd());
    }
}
