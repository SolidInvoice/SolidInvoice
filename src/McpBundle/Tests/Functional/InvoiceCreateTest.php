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

namespace SolidInvoice\McpBundle\Tests\Functional;

use Mcp\Exception\ToolCallException;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Mcp\InvoiceWriteTools;
use SolidInvoice\McpBundle\Security\McpOAuthAuthenticator;
use SolidInvoice\McpBundle\Security\McpScope;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Mcp\QuoteWriteTools;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Uid\Ulid;
use Zenstruck\Foundry\Test\Factories;

/**
 * @group functional
 */
final class InvoiceCreateTest extends KernelTestCase
{
    use EnsureApplicationInstalled;
    use Factories;

    public function testCreateInvoiceWithLineItems(): void
    {
        $this->activateScopes([McpScope::Write->value]);

        $client = ClientFactory::createOne([
            'company' => $this->company,
            'currencyCode' => 'USD',
        ])->_real();

        $tool = self::getContainer()->get(InvoiceWriteTools::class);
        self::assertInstanceOf(InvoiceWriteTools::class, $tool);

        $result = $tool->createInvoice(
            $client->getId()->toRfc4122(),
            [
                ['description' => 'Consulting, 10 hours', 'price' => 10000, 'qty' => 10],
                ['description' => 'Setup fee', 'price' => 5000, 'qty' => 1],
            ],
            invoice_date: '2026-04-01',
            due: '2026-05-01',
            terms: 'Net 30',
            notes: 'Thanks for your business',
        );

        self::assertArrayHasKey('id', $result);
        self::assertArrayHasKey('invoice_number', $result);
        self::assertNotEmpty($result['invoice_number']);
        self::assertSame('105000', $result['total']);
        self::assertSame('Net 30', $result['terms']);
        self::assertSame('Thanks for your business', $result['notes']);

        $invoice = self::getContainer()->get('doctrine')->getRepository(Invoice::class)->find(Ulid::fromString($result['id']));
        self::assertInstanceOf(Invoice::class, $invoice);
        self::assertSame($this->company->getId()->toRfc4122(), $invoice->getCompany()->getId()->toRfc4122());
        self::assertCount(2, $invoice->getLines());
    }

    public function testCreateInvoiceRequiresAtLeastOneLine(): void
    {
        $this->activateScopes([McpScope::Write->value]);

        $client = ClientFactory::createOne(['company' => $this->company])->_real();

        $tool = self::getContainer()->get(InvoiceWriteTools::class);
        self::assertInstanceOf(InvoiceWriteTools::class, $tool);

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('At least one line item');

        $tool->createInvoice($client->getId()->toRfc4122(), []);
    }

    public function testCreateInvoiceRejectsBadLine(): void
    {
        $this->activateScopes([McpScope::Write->value]);

        $client = ClientFactory::createOne(['company' => $this->company])->_real();

        $tool = self::getContainer()->get(InvoiceWriteTools::class);
        self::assertInstanceOf(InvoiceWriteTools::class, $tool);

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('description');

        $tool->createInvoice(
            $client->getId()->toRfc4122(),
            [['price' => 1000, 'qty' => 1]],
        );
    }

    public function testCreateInvoiceReadOnlyTokenRejected(): void
    {
        $this->activateScopes([McpScope::Read->value]);

        $client = ClientFactory::createOne(['company' => $this->company])->_real();

        $tool = self::getContainer()->get(InvoiceWriteTools::class);
        self::assertInstanceOf(InvoiceWriteTools::class, $tool);

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('mcp:write');

        $tool->createInvoice(
            $client->getId()->toRfc4122(),
            [['description' => 'Thing', 'price' => 100, 'qty' => 1]],
        );
    }

    public function testCreateInvoiceWithPercentageDiscount(): void
    {
        $this->activateScopes([McpScope::Write->value]);

        $client = ClientFactory::createOne(['company' => $this->company])->_real();

        $tool = self::getContainer()->get(InvoiceWriteTools::class);
        self::assertInstanceOf(InvoiceWriteTools::class, $tool);

        $result = $tool->createInvoice(
            $client->getId()->toRfc4122(),
            [['description' => 'Widget', 'price' => 10000, 'qty' => 1]],
            discount_type: 'percentage',
            discount_value: 10,
        );

        // 10000 - 10% = 9000
        self::assertSame('9000', $result['total']);
    }

    public function testCreateInvoiceWithMoneyDiscount(): void
    {
        $this->activateScopes([McpScope::Write->value]);

        $client = ClientFactory::createOne(['company' => $this->company])->_real();

        $tool = self::getContainer()->get(InvoiceWriteTools::class);
        self::assertInstanceOf(InvoiceWriteTools::class, $tool);

        $result = $tool->createInvoice(
            $client->getId()->toRfc4122(),
            [['description' => 'Service', 'price' => 20000, 'qty' => 1]],
            discount_type: 'money',
            discount_value: 5000,
        );

        // 20000 - 5000 = 15000
        self::assertSame('15000', $result['total']);
    }

    public function testCreateInvoiceRejectsInvalidDiscountType(): void
    {
        $this->activateScopes([McpScope::Write->value]);

        $client = ClientFactory::createOne(['company' => $this->company])->_real();

        $tool = self::getContainer()->get(InvoiceWriteTools::class);
        self::assertInstanceOf(InvoiceWriteTools::class, $tool);

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Invalid discount_type');

        $tool->createInvoice(
            $client->getId()->toRfc4122(),
            [['description' => 'Thing', 'price' => 100, 'qty' => 1]],
            discount_type: 'bogus',
            discount_value: 5,
        );
    }

    public function testCreateInvoiceRejectsDiscountTypeWithoutValue(): void
    {
        $this->activateScopes([McpScope::Write->value]);

        $client = ClientFactory::createOne(['company' => $this->company])->_real();

        $tool = self::getContainer()->get(InvoiceWriteTools::class);
        self::assertInstanceOf(InvoiceWriteTools::class, $tool);

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('discount_value is required');

        $tool->createInvoice(
            $client->getId()->toRfc4122(),
            [['description' => 'Thing', 'price' => 100, 'qty' => 1]],
            discount_type: 'percentage',
        );
    }

    public function testCreateQuoteWithLineItems(): void
    {
        $this->activateScopes([McpScope::Write->value]);

        $client = ClientFactory::createOne(['company' => $this->company])->_real();

        $tool = self::getContainer()->get(QuoteWriteTools::class);
        self::assertInstanceOf(QuoteWriteTools::class, $tool);

        $result = $tool->createQuote(
            $client->getId()->toRfc4122(),
            [['description' => 'Estimate', 'price' => 25000, 'qty' => 2]],
            due: '2026-06-01',
            notes: 'Rough estimate',
        );

        self::assertArrayHasKey('id', $result);
        self::assertArrayHasKey('quote_number', $result);
        self::assertSame('50000', $result['total']);
        self::assertSame('Rough estimate', $result['notes']);

        $quote = self::getContainer()->get('doctrine')->getRepository(Quote::class)->find(Ulid::fromString($result['id']));
        self::assertInstanceOf(Quote::class, $quote);
        self::assertSame($this->company->getId()->toRfc4122(), $quote->getCompany()->getId()->toRfc4122());
        self::assertCount(1, $quote->getLines());
    }

    /**
     * @param list<string> $scopes
     */
    private function activateScopes(array $scopes): void
    {
        $container = self::getContainer();

        $stack = $container->get(RequestStack::class);
        self::assertInstanceOf(RequestStack::class, $stack);

        while ($stack->getMainRequest() !== null) {
            $stack->pop();
        }

        $request = new Request();
        $request->attributes->set(McpOAuthAuthenticator::ATTR_SCOPES, $scopes);
        $stack->push($request);

        $selector = $container->get(CompanySelector::class);
        self::assertInstanceOf(CompanySelector::class, $selector);
        $selector->switchCompany($this->company->getId());
    }
}
