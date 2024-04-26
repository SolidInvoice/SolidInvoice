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

        self::assertSame('1', $generator->generate(new Invoice(), ['field' => 'invoiceId']));
        self::assertSame('1', $generator->generate(new Invoice(), ['field' => 'invoiceId']));

        self::assertSame('1', $generator->generate(new Quote(), ['field' => 'quoteId']));
        self::assertSame('1', $generator->generate(new Quote(), ['field' => 'quoteId']));
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
}
