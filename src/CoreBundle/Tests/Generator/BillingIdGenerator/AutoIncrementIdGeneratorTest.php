<?php
declare(strict_types=1);

namespace SolidInvoice\CoreBundle\Tests\Generator\BillingIdGenerator;

use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\CoreBundle\Generator\BillingIdGenerator\AutoIncrementIdGenerator;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use SolidInvoice\QuoteBundle\Entity\Quote;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

/** @covers \SolidInvoice\CoreBundle\Generator\BillingIdGenerator\AutoIncrementIdGenerator */
final class AutoIncrementIdGeneratorTest extends KernelTestCase
{
    use EnsureApplicationInstalled;
    use Factories;

    public function testItGeneratesTheSameIdWhenNotSavingAnyEntities(): void
    {
        $generator = new AutoIncrementIdGenerator(self::getContainer()->get('doctrine'));

        self::assertSame('1', $generator->generate(new Invoice(), 'invoiceId'));
        self::assertSame('1', $generator->generate(new Invoice(), 'invoiceId'));

        self::assertSame('1', $generator->generate(new Quote(), 'quoteId'));
        self::assertSame('1', $generator->generate(new Quote(), 'quoteId'));
    }

    public function testItIncrementsTheId(): void
    {
        $client = ClientFactory::new([]);

        InvoiceFactory::createOne(['client' => $client]);

        $generator = new AutoIncrementIdGenerator(self::getContainer()->get('doctrine'));

        self::assertSame('2', $generator->generate(new Invoice(), 'invoiceId'));

        InvoiceFactory::createOne(['client' => $client]);

        self::assertSame('3', $generator->generate(new Invoice(), 'invoiceId'));

        InvoiceFactory::createMany(5, ['client' => $client]);

        self::assertSame('8', $generator->generate(new Invoice(), 'invoiceId'));
        self::assertSame('1', $generator->generate(new Quote(), 'quoteId'));
    }
}
