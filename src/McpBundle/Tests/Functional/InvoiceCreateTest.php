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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\CoreBundle\Billing\LineName;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Enum\PaymentTerms;
use SolidInvoice\InvoiceBundle\Mcp\InvoiceWriteTools;
use SolidInvoice\McpBundle\Security\McpOAuthAuthenticator;
use SolidInvoice\McpBundle\Security\McpScope;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Mcp\QuoteWriteTools;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Uid\Ulid;

#[Group('functional')]
final class InvoiceCreateTest extends KernelTestCase
{
    use EnsureApplicationInstalled;

    public function testCreateInvoiceWithLineItems(): void
    {
        $this->activateScopes([McpScope::Write->value]);

        $client = ClientFactory::createOne([
            'company' => $this->company,
            'currencyCode' => 'USD',
        ]);

        $tool = self::getContainer()->get(InvoiceWriteTools::class);
        self::assertInstanceOf(InvoiceWriteTools::class, $tool);

        $result = $tool->createInvoice(
            $client->getId()
                ->toBase58(),
            [
                ['description' => 'Consulting, 10 hours', 'price' => '100', 'qty' => 10],
                ['description' => 'Setup fee', 'price' => '50', 'qty' => 1],
            ],
            invoice_date: '2026-04-01',
            due: '2026-05-01',
            terms: 'Net 30',
            notes: 'Thanks for your business',
        );

        self::assertNotEmpty($result->invoice_number);
        self::assertSame('1050.00', $result->total);
        self::assertSame('Net 30', $result->terms);
        self::assertSame('Thanks for your business', $result->notes);

        $invoice = self::getContainer()->get('doctrine')->getRepository(Invoice::class)->find(Ulid::fromString((string) $result->id));
        self::assertInstanceOf(Invoice::class, $invoice);
        self::assertSame($this->company->getId()->toBase58(), $invoice->getCompany()->getId()->toBase58());
        self::assertCount(2, $invoice->getLines());
    }

    public function testPaymentTermsSetTheDueDate(): void
    {
        $this->activateScopes([McpScope::Write->value]);

        $client = ClientFactory::createOne(['company' => $this->company, 'currencyCode' => 'USD']);
        $tool = self::getContainer()->get(InvoiceWriteTools::class);
        self::assertInstanceOf(InvoiceWriteTools::class, $tool);

        $result = $tool->createInvoice(
            $client->getId()->toBase58(),
            [['name' => 'Retainer', 'price' => '10', 'qty' => 1]],
            invoice_date: '2026-04-01',
            payment_terms: 'net_14',
        );

        self::assertSame(PaymentTerms::Net14, $result->payment_terms);
        self::assertStringStartsWith('2026-04-15', (string) $result->due);
    }

    public function testADueDateCannotBeCombinedWithAPresetTerm(): void
    {
        $this->activateScopes([McpScope::Write->value]);

        $client = ClientFactory::createOne(['company' => $this->company, 'currencyCode' => 'USD']);
        $tool = self::getContainer()->get(InvoiceWriteTools::class);
        self::assertInstanceOf(InvoiceWriteTools::class, $tool);

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessageIsOrContains('payment_terms "net_30" sets the due date itself');

        $tool->createInvoice(
            $client->getId()->toBase58(),
            [['name' => 'Retainer', 'price' => '10', 'qty' => 1]],
            due: '2030-12-31',
            payment_terms: 'net_30',
        );
    }

    /**
     * A tool call goes to the database without passing a validator, so a name longer than
     * the line's VARCHAR(255) would fail on flush. It is kept, as the description, and the
     * name is cut to fit. SQLite ignores the column width, hence the explicit length check.
     */
    public function testAnOverLongLineNameIsKeptAsTheDescription(): void
    {
        $this->activateScopes([McpScope::Write->value]);

        $client = ClientFactory::createOne([
            'company' => $this->company,
            'currencyCode' => 'USD',
        ]);

        $tool = self::getContainer()->get(InvoiceWriteTools::class);
        self::assertInstanceOf(InvoiceWriteTools::class, $tool);

        $name = str_repeat('An unreasonably verbose line item name. ', 10);

        $result = $tool->createInvoice(
            $client->getId()
                ->toBase58(),
            [['name' => $name, 'price' => '10', 'qty' => 1]],
        );

        $invoice = self::getContainer()->get('doctrine')->getRepository(Invoice::class)->find(Ulid::fromString((string) $result->id));
        self::assertInstanceOf(Invoice::class, $invoice);

        $line = $invoice->getLines()
            ->first();

        // The exact value, not just a bounded one: a builder that cut the name its own way
        // would still fit the column while disagreeing with every other caller.
        self::assertSame(LineName::fromDescription($name), $line->getName());
        self::assertLessThanOrEqual(LineName::MAX_LENGTH, mb_strlen($line->getName()));
        self::assertSame($name, $line->getDescription());
    }

    public function testCreateInvoiceRequiresAtLeastOneLine(): void
    {
        $this->activateScopes([McpScope::Write->value]);

        $client = ClientFactory::createOne(['company' => $this->company]);

        $tool = self::getContainer()->get(InvoiceWriteTools::class);
        self::assertInstanceOf(InvoiceWriteTools::class, $tool);

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessageIsOrContains('At least one line item');

        $tool->createInvoice($client->getId()->toBase58(), []);
    }

    public function testCreateInvoiceRejectsBadLine(): void
    {
        $this->activateScopes([McpScope::Write->value]);

        $client = ClientFactory::createOne(['company' => $this->company]);

        $tool = self::getContainer()->get(InvoiceWriteTools::class);
        self::assertInstanceOf(InvoiceWriteTools::class, $tool);

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessageIsOrContains('name');

        $tool->createInvoice(
            $client->getId()
                ->toBase58(),
            [['price' => '10', 'qty' => 1]],
        );
    }

    public function testCreateInvoiceReadOnlyTokenRejected(): void
    {
        $this->activateScopes([McpScope::Read->value]);

        $client = ClientFactory::createOne(['company' => $this->company]);

        $tool = self::getContainer()->get(InvoiceWriteTools::class);
        self::assertInstanceOf(InvoiceWriteTools::class, $tool);

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessageIsOrContains('mcp:write');

        $tool->createInvoice(
            $client->getId()
                ->toBase58(),
            [['description' => 'Thing', 'price' => '1', 'qty' => 1]],
        );
    }

    public function testCreateInvoiceWithPercentageDiscount(): void
    {
        $this->activateScopes([McpScope::Write->value]);

        $client = ClientFactory::createOne(['company' => $this->company, 'currencyCode' => 'USD']);

        $tool = self::getContainer()->get(InvoiceWriteTools::class);
        self::assertInstanceOf(InvoiceWriteTools::class, $tool);

        $result = $tool->createInvoice(
            $client->getId()
                ->toBase58(),
            [['description' => 'Widget', 'price' => '100', 'qty' => 1]],
            discount_type: 'percentage',
            discount_value: '10',
        );

        // 100.00 - 10% = 90.00
        self::assertSame('90.00', $result->total);
    }

    public function testCreateInvoiceWithMoneyDiscount(): void
    {
        $this->activateScopes([McpScope::Write->value]);

        $client = ClientFactory::createOne(['company' => $this->company, 'currencyCode' => 'USD']);

        $tool = self::getContainer()->get(InvoiceWriteTools::class);
        self::assertInstanceOf(InvoiceWriteTools::class, $tool);

        $result = $tool->createInvoice(
            $client->getId()
                ->toBase58(),
            [['description' => 'Service', 'price' => '200', 'qty' => 1]],
            discount_type: 'money',
            discount_value: '50.00',
        );

        // 200.00 - 50.00 = 150.00
        self::assertSame('150.00', $result->total);
    }

    /**
     * Line prices and money discounts are read at the client currency's own scale.
     */
    public function testLinePriceAndMoneyDiscountUseTheClientCurrencyScale(): void
    {
        $this->activateScopes([McpScope::Write->value]);

        $client = ClientFactory::createOne(['company' => $this->company, 'currencyCode' => 'BHD']);

        $tool = self::getContainer()->get(InvoiceWriteTools::class);
        self::assertInstanceOf(InvoiceWriteTools::class, $tool);

        $result = $tool->createInvoice(
            $client->getId()->toBase58(),
            [['description' => 'Service', 'price' => '10.125', 'qty' => 1]],
            discount_type: 'money',
            discount_value: '0.125',
        );

        self::assertSame('10.125', $result->lines[0]->price);
        self::assertSame('10.000', $result->total);

        $invoice = self::getContainer()->get('doctrine')->getRepository(Invoice::class)->find(Ulid::fromString((string) $result->id));
        self::assertInstanceOf(Invoice::class, $invoice);
        self::assertTrue($invoice->getLines()->first()->getPrice()->isEqualTo(10125));
    }

    /**
     * @return iterable<string, array{array<string, mixed>, string|null, string|null, string}>
     */
    public static function invalidMoneyInputs(): iterable
    {
        yield 'price as a JSON number' => [['description' => 'X', 'price' => 10, 'qty' => 1], null, null, 'lines[0].price must be a decimal string'];
        yield 'price not a number' => [['description' => 'X', 'price' => 'abc', 'qty' => 1], null, null, 'lines[0].price "abc" is not a non-negative decimal'];
        yield 'negative price' => [['description' => 'X', 'price' => '-1', 'qty' => 1], null, null, 'lines[0].price "-1" is not a non-negative decimal'];
        yield 'price too precise' => [['description' => 'X', 'price' => '1.005', 'qty' => 1], null, null, 'more decimal places than USD allows (2)'];
        yield 'money discount too precise' => [['description' => 'X', 'price' => '1', 'qty' => 1], 'money', '0.001', 'discount_value "0.001" has more decimal places'];
        yield 'percentage discount not a number' => [['description' => 'X', 'price' => '1', 'qty' => 1], 'percentage', 'ten', 'discount_value "ten" is not a non-negative percentage'];
    }

    /**
     * @param array<string, mixed> $line
     */
    #[DataProvider('invalidMoneyInputs')]
    public function testCreateInvoiceRejectsInvalidMoneyInput(array $line, ?string $discountType, ?string $discountValue, string $message): void
    {
        $this->activateScopes([McpScope::Write->value]);

        $client = ClientFactory::createOne(['company' => $this->company, 'currencyCode' => 'USD']);

        $tool = self::getContainer()->get(InvoiceWriteTools::class);
        self::assertInstanceOf(InvoiceWriteTools::class, $tool);

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessageIsOrContains($message);

        $tool->createInvoice($client->getId()->toBase58(), [$line], discount_type: $discountType, discount_value: $discountValue);
    }

    public function testCreateInvoiceRejectsInvalidDiscountType(): void
    {
        $this->activateScopes([McpScope::Write->value]);

        $client = ClientFactory::createOne(['company' => $this->company]);

        $tool = self::getContainer()->get(InvoiceWriteTools::class);
        self::assertInstanceOf(InvoiceWriteTools::class, $tool);

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessageIsOrContains('Invalid discount_type');

        $tool->createInvoice(
            $client->getId()
                ->toBase58(),
            [['description' => 'Thing', 'price' => '1', 'qty' => 1]],
            discount_type: 'bogus',
            discount_value: '5',
        );
    }

    public function testCreateInvoiceRejectsDiscountTypeWithoutValue(): void
    {
        $this->activateScopes([McpScope::Write->value]);

        $client = ClientFactory::createOne(['company' => $this->company]);

        $tool = self::getContainer()->get(InvoiceWriteTools::class);
        self::assertInstanceOf(InvoiceWriteTools::class, $tool);

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessageIsOrContains('discount_value is required');

        $tool->createInvoice(
            $client->getId()
                ->toBase58(),
            [['description' => 'Thing', 'price' => '1', 'qty' => 1]],
            discount_type: 'percentage',
        );
    }

    public function testCreateQuoteWithLineItems(): void
    {
        $this->activateScopes([McpScope::Write->value]);

        $client = ClientFactory::createOne(['company' => $this->company, 'currencyCode' => 'USD']);

        $tool = self::getContainer()->get(QuoteWriteTools::class);
        self::assertInstanceOf(QuoteWriteTools::class, $tool);

        $result = $tool->createQuote(
            $client->getId()
                ->toBase58(),
            [['description' => 'Estimate', 'price' => '250', 'qty' => 2]],
            due: '2026-06-01',
            notes: 'Rough estimate',
        );

        self::assertNotEmpty($result->quote_number);
        self::assertSame('500.00', $result->total);
        self::assertSame('Rough estimate', $result->notes);

        $quote = self::getContainer()->get('doctrine')->getRepository(Quote::class)->find(Ulid::fromString((string) $result->id));
        self::assertInstanceOf(Quote::class, $quote);
        self::assertSame($this->company->getId()->toBase58(), $quote->getCompany()->getId()->toBase58());
        self::assertCount(1, $quote->getLines());
    }

    public function testASuppliedInvoiceNumberIsKeptAndCannotBeReused(): void
    {
        $this->activateScopes([McpScope::Write->value]);

        $client = ClientFactory::createOne(['company' => $this->company, 'currencyCode' => 'USD']);
        $tool = self::getContainer()->get(InvoiceWriteTools::class);
        self::assertInstanceOf(InvoiceWriteTools::class, $tool);

        $result = $tool->createInvoice($client->getId()->toBase58(), [['name' => 'Work', 'price' => '10', 'qty' => 1]], invoice_id: 'MCP-7');

        self::assertSame('MCP-7', $result->invoice_number);

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessageIsOrContains('Invoice number "MCP-7" is already in use.');

        $tool->createInvoice($client->getId()->toBase58(), [['name' => 'Work', 'price' => '10', 'qty' => 1]], invoice_id: 'MCP-7');
    }

    public function testASuppliedQuoteNumberIsKeptAndCannotBeReused(): void
    {
        $this->activateScopes([McpScope::Write->value]);

        $client = ClientFactory::createOne(['company' => $this->company]);
        $tool = self::getContainer()->get(QuoteWriteTools::class);
        self::assertInstanceOf(QuoteWriteTools::class, $tool);

        $result = $tool->createQuote($client->getId()->toBase58(), [['name' => 'Work', 'price' => '10', 'qty' => 1]], quote_id: 'MCP-Q-7');

        self::assertSame('MCP-Q-7', $result->quote_number);

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessageIsOrContains('Quote number "MCP-Q-7" is already in use.');

        $tool->createQuote($client->getId()->toBase58(), [['name' => 'Work', 'price' => '10', 'qty' => 1]], quote_id: 'MCP-Q-7');
    }

    /**
     * @param list<string> $scopes
     */
    private function activateScopes(array $scopes): void
    {
        $container = self::getContainer();

        $stack = $container->get(RequestStack::class);
        self::assertInstanceOf(RequestStack::class, $stack);

        while ($stack->getMainRequest() instanceof Request) {
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
