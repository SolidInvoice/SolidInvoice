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

namespace SolidInvoice\CoreBundle\Tests\Generator\BillingIdGenerator;

use PHPUnit\Framework\Attributes\CoversClass;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\CoreBundle\Generator\BillingIdGenerator\AutoIncrementIdGenerator;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\InvoiceBundle\Entity\CreditNote;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Test\Factory\CreditNoteFactory;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use SolidInvoice\QuoteBundle\Entity\Quote;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[CoversClass(AutoIncrementIdGenerator::class)]
final class AutoIncrementIdGeneratorTest extends KernelTestCase
{
    use EnsureApplicationInstalled;

    public function testItGeneratesTheSameIdWhenNotSavingAnyEntities(): void
    {
        $generator = new AutoIncrementIdGenerator(self::getContainer()->get('doctrine'));

        self::assertSame('1', $generator->generate(new Invoice(), ['field' => 'invoiceId']));
        self::assertSame('1', $generator->generate(new Invoice(), ['field' => 'invoiceId']));

        self::assertSame('1', $generator->generate(new Quote(), ['field' => 'quoteId']));
        self::assertSame('1', $generator->generate(new Quote(), ['field' => 'quoteId']));

        self::assertSame('1', $generator->generate(new CreditNote(), ['field' => 'creditNoteId']));
        self::assertSame('1', $generator->generate(new CreditNote(), ['field' => 'creditNoteId']));
    }

    /**
     * A draft credit note is persisted with its default empty id before a number is
     * ever assigned (the id is only set on the `issue` workflow transition). That row's
     * id is shorter than the prefix, which made the SUBSTRING length argument negative.
     * PostgreSQL rejects that outright ("negative substring length not allowed");
     * SQLite and MySQL/MariaDB silently tolerate it, so this only ever broke on
     * PostgreSQL in CI.
     */
    public function testItSkipsRowsShorterThanThePrefixAndSuffix(): void
    {
        $client = ClientFactory::new([]);

        CreditNoteFactory::createOne(['client' => $client, 'creditNoteId' => '']);
        CreditNoteFactory::createOne(['client' => $client, 'creditNoteId' => 'CN-1']);

        $generator = new AutoIncrementIdGenerator(self::getContainer()->get('doctrine'));

        self::assertSame('2', $generator->generate(new CreditNote(), ['field' => 'creditNoteId', 'prefix' => 'CN-', 'suffix' => '']));
    }

    public function testCreditNoteSequenceIsIndependentOfInvoiceAndQuoteSequences(): void
    {
        $client = ClientFactory::new([]);

        InvoiceFactory::createOne(['client' => $client, 'invoiceId' => '1']);
        InvoiceFactory::createOne(['client' => $client, 'invoiceId' => '2']);

        $generator = new AutoIncrementIdGenerator(self::getContainer()->get('doctrine'));

        // The first-ever credit note starts at 1, unaffected by the two invoices already saved.
        self::assertSame('1', $generator->generate(new CreditNote(), ['field' => 'creditNoteId', 'prefix' => 'CN-', 'suffix' => '']));

        CreditNoteFactory::createOne(['client' => $client, 'creditNoteId' => 'CN-1']);

        self::assertSame('2', $generator->generate(new CreditNote(), ['field' => 'creditNoteId', 'prefix' => 'CN-', 'suffix' => '']));

        // Saving another credit note does not disturb the invoice sequence.
        self::assertSame('3', $generator->generate(new Invoice(), ['field' => 'invoiceId']));
    }

    public function testItIncrementsTheId(): void
    {
        $client = ClientFactory::new([]);

        InvoiceFactory::createOne(['client' => $client, 'invoiceId' => '1']);

        $generator = new AutoIncrementIdGenerator(self::getContainer()->get('doctrine'));

        self::assertSame('2', $generator->generate(new Invoice(), ['field' => 'invoiceId']));

        InvoiceFactory::createOne(['client' => $client, 'invoiceId' => '2']);

        self::assertSame('3', $generator->generate(new Invoice(), ['field' => 'invoiceId']));

        InvoiceFactory::createOne(['client' => $client, 'invoiceId' => '101']);

        self::assertSame('102', $generator->generate(new Invoice(), ['field' => 'invoiceId']));
        self::assertSame('1', $generator->generate(new Quote(), ['field' => 'quoteId']));
    }

    public function testItIncrementsTheIdWithPrefix(): void
    {
        $client = ClientFactory::new([]);

        InvoiceFactory::createOne(['client' => $client, 'invoiceId' => 'INV-1']);

        $generator = new AutoIncrementIdGenerator(self::getContainer()->get('doctrine'));

        self::assertSame('2', $generator->generate(new Invoice(), ['field' => 'invoiceId', 'prefix' => 'INV-', 'suffix' => '']));

        InvoiceFactory::createOne(['client' => $client, 'invoiceId' => 'INV-2']);

        self::assertSame('3', $generator->generate(new Invoice(), ['field' => 'invoiceId', 'prefix' => 'INV-', 'suffix' => '']));

        InvoiceFactory::createOne(['client' => $client, 'invoiceId' => 'INV-101']);

        self::assertSame('102', $generator->generate(new Invoice(), ['field' => 'invoiceId', 'prefix' => 'INV-', 'suffix' => '']));
    }

    public function testItIncrementsTheIdWithSuffix(): void
    {
        $client = ClientFactory::new([]);

        InvoiceFactory::createOne(['client' => $client, 'invoiceId' => '1-00']);

        $generator = new AutoIncrementIdGenerator(self::getContainer()->get('doctrine'));

        self::assertSame('2', $generator->generate(new Invoice(), ['field' => 'invoiceId', 'prefix' => '', 'suffix' => '-00']));

        InvoiceFactory::createOne(['client' => $client, 'invoiceId' => '2-00']);

        self::assertSame('3', $generator->generate(new Invoice(), ['field' => 'invoiceId', 'prefix' => '', 'suffix' => '-00']));

        InvoiceFactory::createOne(['client' => $client, 'invoiceId' => '101-00']);

        self::assertSame('102', $generator->generate(new Invoice(), ['field' => 'invoiceId', 'prefix' => '', 'suffix' => '-00']));
    }

    public function testItIncrementsTheIdWithPrefixAndSuffix(): void
    {
        $client = ClientFactory::new([]);

        InvoiceFactory::createOne(['client' => $client, 'invoiceId' => 'INV-1-00']);

        $generator = new AutoIncrementIdGenerator(self::getContainer()->get('doctrine'));

        self::assertSame('2', $generator->generate(new Invoice(), ['field' => 'invoiceId', 'prefix' => 'INV-', 'suffix' => '-00']));

        InvoiceFactory::createOne(['client' => $client, 'invoiceId' => 'INV-2-00']);

        self::assertSame('3', $generator->generate(new Invoice(), ['field' => 'invoiceId', 'prefix' => 'INV-', 'suffix' => '-00']));

        InvoiceFactory::createOne(['client' => $client, 'invoiceId' => 'INV-101-00']);

        self::assertSame('102', $generator->generate(new Invoice(), ['field' => 'invoiceId', 'prefix' => 'INV-', 'suffix' => '-00']));
    }
}
