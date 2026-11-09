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

use Carbon\CarbonImmutable;
use Mcp\Exception\ToolCallException;
use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Mcp\ClientReadTools;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\ClientBundle\Test\Factory\ContactFactory;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\DashboardBundle\Mcp\AnalyticsTools;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\Line;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Enum\RecurringInvoiceStatus;
use SolidInvoice\InvoiceBundle\Mcp\InvoiceReadTools;
use SolidInvoice\InvoiceBundle\Mcp\RecurringInvoiceReadTools;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use SolidInvoice\InvoiceBundle\Test\Factory\RecurringInvoiceFactory;
use SolidInvoice\McpBundle\Mcp\Output\AmountOutput;
use SolidInvoice\McpBundle\Mcp\Output\Entity\ClientOutput;
use SolidInvoice\McpBundle\Mcp\Output\Entity\ContactOutput;
use SolidInvoice\McpBundle\Mcp\Output\Entity\InvoiceOutput;
use SolidInvoice\McpBundle\Mcp\Output\Entity\PaymentMethodOutput;
use SolidInvoice\McpBundle\Mcp\Output\Entity\PaymentOutput;
use SolidInvoice\McpBundle\Mcp\Output\Entity\QuoteOutput;
use SolidInvoice\McpBundle\Mcp\Output\Entity\RecurringInvoiceOutput;
use SolidInvoice\McpBundle\Mcp\Output\Entity\TaxOutput;
use SolidInvoice\McpBundle\Mcp\Output\RevenueByPeriodOutput;
use SolidInvoice\McpBundle\Mcp\Output\RevenuePeriodOutput;
use SolidInvoice\McpBundle\Mcp\Output\StatusCountOutput;
use SolidInvoice\McpBundle\Security\McpOAuthAuthenticator;
use SolidInvoice\McpBundle\Security\McpScope;
use SolidInvoice\PaymentBundle\Enum\PaymentStatus;
use SolidInvoice\PaymentBundle\Mcp\PaymentMethodReadTools;
use SolidInvoice\PaymentBundle\Mcp\PaymentReadTools;
use SolidInvoice\PaymentBundle\Test\Factory\PaymentFactory;
use SolidInvoice\PaymentBundle\Test\Factory\PaymentMethodFactory;
use SolidInvoice\QuoteBundle\Enum\QuoteStatus;
use SolidInvoice\QuoteBundle\Mcp\QuoteReadTools;
use SolidInvoice\QuoteBundle\Test\Factory\QuoteFactory;
use SolidInvoice\SettingsBundle\Mcp\SettingsReadTools;
use SolidInvoice\TaxBundle\Mcp\TaxReadTools;
use SolidInvoice\TaxBundle\Test\Factory\TaxFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Uid\Ulid;

/**
 * Exercises the Phase 2 read tools end-to-end within the kernel, simulating
 * the scopes a real OAuth-authenticated request would carry.
 */
#[Group('functional')]
final class ReadToolFlowTest extends KernelTestCase
{
    use EnsureApplicationInstalled;

    public function testCompanyInfoToolReturnsActiveCompany(): void
    {
        $this->setActiveScopes([McpScope::Read->value]);

        $tool = self::getContainer()->get(SettingsReadTools::class);
        self::assertInstanceOf(SettingsReadTools::class, $tool);

        $result = $tool->getCompanyInfo();

        self::assertSame($this->company->getId()->toBase58(), $result->id);
        self::assertSame('SolidInvoice', $result->name);
    }

    public function testGetInvoiceAcceptsBase58AndRfc4122Ids(): void
    {
        $this->setActiveScopes([McpScope::Read->value]);

        $invoice = InvoiceFactory::createOne([
            'company' => $this->company,
            'client' => ClientFactory::createOne(['company' => $this->company]),
            'status' => InvoiceStatus::Pending,
        ]);

        $tool = $this->tool(InvoiceReadTools::class);

        $byBase58 = $tool->getInvoice($invoice->getId()->toBase58());
        self::assertSame($invoice->getId()->toBase58(), $byBase58->id);
        self::assertSame(InvoiceStatus::Pending, $byBase58->status);

        // Output is base58, but a client holding an RFC 4122 id from elsewhere must still find it.
        $hex = bin2hex($invoice->getId()->toBinary());
        $rfc4122 = sprintf('%s-%s-%s-%s-%s', substr($hex, 0, 8), substr($hex, 8, 4), substr($hex, 12, 4), substr($hex, 16, 4), substr($hex, 20));
        self::assertMatchesRegularExpression('/^[0-9a-f]{8}-([0-9a-f]{4}-){3}[0-9a-f]{12}$/', $rfc4122);

        $byRfc4122 = $tool->getInvoice($rfc4122);
        self::assertSame($invoice->getId()->toBase58(), $byRfc4122->id);
    }

    public function testGetInvoiceReportsAMissingIdAsNotFound(): void
    {
        $this->setActiveScopes([McpScope::Read->value]);

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessageIsOrContains('not found');

        $this->tool(InvoiceReadTools::class)->getInvoice(new Ulid()->toBase58());
    }

    public function testListInvoicesWithOverdueStatusReturnsOverdueOnlyOldestDueFirst(): void
    {
        $this->setActiveScopes([McpScope::Read->value]);

        $client = ClientFactory::createOne(['company' => $this->company]);
        $recent = InvoiceFactory::createOne([
            'company' => $this->company,
            'client' => $client,
            'status' => InvoiceStatus::Overdue,
            'due' => CarbonImmutable::now()->subDays(2),
        ]);
        $oldest = InvoiceFactory::createOne([
            'company' => $this->company,
            'client' => $client,
            'status' => InvoiceStatus::Overdue,
            'due' => CarbonImmutable::now()->subDays(10),
        ]);
        InvoiceFactory::createOne([
            'company' => $this->company,
            'client' => $client,
            'status' => InvoiceStatus::Pending,
            'due' => CarbonImmutable::now()->addDays(10),
        ]);

        $result = $this->tool(InvoiceReadTools::class)->listInvoices(status: 'overdue');

        self::assertSame(2, $result->total);
        self::assertSame(1, $result->page);
        self::assertSame(25, $result->items_per_page);
        self::assertSame(
            [$oldest->getId()->toBase58(), $recent->getId()->toBase58()],
            array_map(static fn (InvoiceOutput $invoice): ?string => $invoice->id, $result->results),
        );
    }

    public function testListInvoicesFiltersByClientAndPaginates(): void
    {
        $this->setActiveScopes([McpScope::Read->value]);

        $client = ClientFactory::createOne(['company' => $this->company]);
        InvoiceFactory::createMany(3, ['company' => $this->company, 'client' => $client, 'status' => InvoiceStatus::Pending]);
        InvoiceFactory::createOne([
            'company' => $this->company,
            'client' => ClientFactory::createOne(['company' => $this->company]),
            'status' => InvoiceStatus::Pending,
        ]);

        $tool = $this->tool(InvoiceReadTools::class);

        $page2 = $tool->listInvoices(client_id: $client->getId()->toBase58(), page: 2, items_per_page: 2);
        self::assertSame(3, $page2->total);
        self::assertSame(2, $page2->page);
        self::assertSame(2, $page2->items_per_page);
        self::assertCount(1, $page2->results);

        self::assertSame(100, $tool->listInvoices(items_per_page: 500)->items_per_page);
        self::assertSame(1, $tool->listInvoices(items_per_page: 0)->items_per_page);
    }

    public function testListInvoicesRejectsAnUnknownStatus(): void
    {
        $this->setActiveScopes([McpScope::Read->value]);

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessageIsOrContains('Invalid status');

        $this->tool(InvoiceReadTools::class)->listInvoices(status: 'late');
    }

    public function testQuoteToolsGetAndFilterByStatus(): void
    {
        $this->setActiveScopes([McpScope::Read->value]);

        $client = ClientFactory::createOne(['company' => $this->company]);
        $accepted = QuoteFactory::createOne(['company' => $this->company, 'client' => $client, 'status' => QuoteStatus::Accepted]);
        QuoteFactory::createOne(['company' => $this->company, 'client' => $client, 'status' => QuoteStatus::Draft]);

        $tool = $this->tool(QuoteReadTools::class);

        self::assertSame($accepted->getId()->toBase58(), $tool->getQuote($accepted->getId()->toBase58())->id);

        $list = $tool->listQuotes(status: 'accepted', client_id: $client->getId()->toBase58());
        self::assertSame(1, $list->total);
        self::assertInstanceOf(QuoteOutput::class, $list->results[0]);
        self::assertSame($accepted->getId()->toBase58(), $list->results[0]->id);

        self::assertSame(2, $tool->listQuotes()->total);
    }

    public function testRecurringInvoiceToolsGetAndList(): void
    {
        $this->setActiveScopes([McpScope::Read->value]);

        $recurring = RecurringInvoiceFactory::createOne([
            'company' => $this->company,
            'client' => ClientFactory::createOne(['company' => $this->company]),
            'status' => RecurringInvoiceStatus::Active,
        ]);

        $tool = $this->tool(RecurringInvoiceReadTools::class);

        self::assertSame($recurring->getId()->toBase58(), $tool->getRecurringInvoice($recurring->getId()->toBase58())->id);

        $list = $tool->listRecurringInvoices(status: 'active');
        self::assertSame(1, $list->total);
        self::assertInstanceOf(RecurringInvoiceOutput::class, $list->results[0]);
        self::assertSame(0, $tool->listRecurringInvoices(status: 'paused')->total);
    }

    public function testClientAndContactToolsGetAndList(): void
    {
        $this->setActiveScopes([McpScope::Read->value]);

        $client = ClientFactory::createOne(['company' => $this->company, 'name' => 'Listed']);
        $contact = ContactFactory::createOne(['company' => $this->company, 'client' => $client]);
        ContactFactory::createOne([
            'company' => $this->company,
            'client' => ClientFactory::createOne(['company' => $this->company]),
        ]);

        $tool = $this->tool(ClientReadTools::class);

        self::assertSame('Listed', $tool->getClient($client->getId()->toBase58())->name);
        self::assertSame(2, $tool->listClients()->total);
        self::assertInstanceOf(ClientOutput::class, $tool->listClients()->results[0]);

        self::assertSame($contact->getEmail(), $tool->getContact($contact->getId()->toBase58())->email);

        $contacts = $tool->listContacts(client_id: $client->getId()->toBase58());
        self::assertSame(1, $contacts->total);
        self::assertInstanceOf(ContactOutput::class, $contacts->results[0]);
        self::assertSame($contact->getId()->toBase58(), $contacts->results[0]->id);
        self::assertSame(2, $tool->listContacts()->total);
    }

    public function testPaymentToolsGetAndFilterByInvoice(): void
    {
        $this->setActiveScopes([McpScope::Read->value]);

        $client = ClientFactory::createOne(['company' => $this->company]);
        $invoice = InvoiceFactory::createOne(['company' => $this->company, 'client' => $client, 'status' => InvoiceStatus::Pending]);
        $payment = PaymentFactory::createOne(['company' => $this->company, 'client' => $client, 'invoice' => $invoice]);
        PaymentFactory::createOne([
            'company' => $this->company,
            'client' => $client,
            'invoice' => InvoiceFactory::createOne(['company' => $this->company, 'client' => $client, 'status' => InvoiceStatus::Pending]),
        ]);

        $tool = $this->tool(PaymentReadTools::class);

        self::assertSame($payment->getId()->toBase58(), $tool->getPayment($payment->getId()->toBase58())->id);

        $byInvoice = $tool->listPayments(invoice_id: $invoice->getId()->toBase58());
        self::assertSame(1, $byInvoice->total);
        self::assertInstanceOf(PaymentOutput::class, $byInvoice->results[0]);
        self::assertSame($payment->getId()->toBase58(), $byInvoice->results[0]->id);
        self::assertSame(2, $tool->listPayments(client_id: $client->getId()->toBase58())->total);
    }

    public function testPaymentMethodAndTaxRateToolsGetAndList(): void
    {
        $this->setActiveScopes([McpScope::Read->value]);

        $method = PaymentMethodFactory::createOne(['company' => $this->company, 'name' => 'Zz Wire']);
        $tax = TaxFactory::createOne(['company' => $this->company, 'name' => 'VAT']);

        $methods = $this->tool(PaymentMethodReadTools::class);
        self::assertSame('Zz Wire', $methods->getPaymentMethod($method->getId()->toBase58())->name);
        $methodList = $methods->listPaymentMethods(items_per_page: 100);
        self::assertContains($method->getId()->toBase58(), array_map(static fn (PaymentMethodOutput $m): ?string => $m->id, $methodList->results));
        self::assertSame(\count($methodList->results), $methodList->total);

        $taxes = $this->tool(TaxReadTools::class);
        self::assertSame('VAT', $taxes->getTaxRate($tax->getId()->toBase58())->name);
        $taxList = $taxes->listTaxRates();
        self::assertSame(1, $taxList->total);
        self::assertInstanceOf(TaxOutput::class, $taxList->results[0]);
    }

    public function testDashboardStatsReturnsDecimalTotalsPerCurrency(): void
    {
        $this->setActiveScopes([McpScope::Read->value]);

        $client = ClientFactory::createOne(['company' => $this->company, 'currencyCode' => 'USD']);
        $this->invoiceOf($client, InvoiceStatus::Pending, 1250);
        $this->invoiceOf($client, InvoiceStatus::Overdue, 500);

        $stats = $this->tool(AnalyticsTools::class)->getDashboardStats();

        self::assertEquals([new AmountOutput('USD', '17.50')], $stats->outstanding);
        self::assertEquals([new AmountOutput('USD', '5.00')], $stats->overdue);
        self::assertEqualsCanonicalizing(
            [new StatusCountOutput(InvoiceStatus::Pending, 1), new StatusCountOutput(InvoiceStatus::Overdue, 1)],
            $stats->counts_by_status,
        );
        self::assertSame(2, $stats->total_invoices);
        self::assertSame(1, $stats->total_clients);
    }

    /**
     * An issued invoice keeps the currency it was issued in when its client's changes, so a
     * client's total is kept per currency, each at that currency's own scale.
     */
    public function testClientTotalsAreKeptPerCurrency(): void
    {
        $this->setActiveScopes([McpScope::Read->value]);

        $client = ClientFactory::createOne(['company' => $this->company, 'currencyCode' => 'USD']);
        $this->invoiceOf($client, InvoiceStatus::Pending, 1250);
        $this->invoiceOf($client, InvoiceStatus::Overdue, 1500, 'JPY');

        $tool = $this->tool(AnalyticsTools::class);
        $clientId = $client->getId()->toBase58();

        self::assertEquals([new AmountOutput('JPY', '1500'), new AmountOutput('USD', '12.50')], $tool->getTotalOutstanding($clientId)->totals);
        self::assertEquals([new AmountOutput('JPY', '1500')], $tool->getTotalOverdue($clientId)->totals);
        self::assertEquals([new AmountOutput('JPY', '1500'), new AmountOutput('USD', '12.50')], $tool->getTotalOutstanding()->totals);
    }

    public function testRevenueByPeriodSumsCapturedPaymentsPerPeriodAndCurrency(): void
    {
        $this->setActiveScopes([McpScope::Read->value]);

        $client = ClientFactory::createOne(['company' => $this->company]);

        foreach ([['2026-03-02 10:00', 1000, 'USD'], ['2026-03-02 15:00', 250, 'USD'], ['2026-03-02 12:00', 1234, 'BHD'], ['2026-03-05 09:00', 700, 'USD']] as [$completed, $amount, $currency]) {
            PaymentFactory::createOne([
                'company' => $this->company,
                'client' => $client,
                'status' => PaymentStatus::Captured,
                'completed' => CarbonImmutable::parse($completed),
                'totalAmount' => $amount,
                'currencyCode' => $currency,
            ]);
        }

        PaymentFactory::createOne(['company' => $this->company, 'client' => $client, 'status' => PaymentStatus::Failed, 'completed' => CarbonImmutable::parse('2026-03-02 11:00'), 'totalAmount' => 999, 'currencyCode' => 'USD']);

        $revenue = $this->tool(AnalyticsTools::class)->getRevenueByPeriod('2026-03-01', '2026-03-31');

        self::assertEquals(new RevenueByPeriodOutput('day', [
            new RevenuePeriodOutput('2026-03-02', [new AmountOutput('BHD', '1.234'), new AmountOutput('USD', '12.50')]),
            new RevenuePeriodOutput('2026-03-05', [new AmountOutput('USD', '7.00')]),
        ]), $revenue);
    }

    public function testReadToolRejectsMissingScope(): void
    {
        $this->setActiveScopes([]);

        $tool = self::getContainer()->get(SettingsReadTools::class);
        self::assertInstanceOf(SettingsReadTools::class, $tool);

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessageIsOrContains('mcp:read');

        $tool->getCompanyInfo();
    }

    public function testClientSummaryReportsDecimalTotalsPerCurrency(): void
    {
        $this->setActiveScopes([McpScope::Read->value]);

        $client = ClientFactory::createOne(['company' => $this->company, 'currencyCode' => 'USD']);
        $invoice = $this->invoiceOf($client, InvoiceStatus::Pending, 10000);
        PaymentFactory::createOne(['company' => $this->company, 'client' => $client, 'invoice' => $invoice, 'status' => PaymentStatus::Captured, 'totalAmount' => 2500, 'currencyCode' => 'USD']);

        $summary = $this->tool(ClientReadTools::class)->getClientSummary($client->getId()->toBase58());

        self::assertSame($client->getId()->toBase58(), $summary->client->id);
        self::assertEquals([new StatusCountOutput(InvoiceStatus::Pending, 1)], $summary->counts_by_status);
        self::assertEquals([new AmountOutput('USD', '100.00')], $summary->total_invoiced);
        self::assertEquals([new AmountOutput('USD', '100.00')], $summary->outstanding);
        self::assertEquals([new AmountOutput('USD', '25.00')], $summary->total_paid);
    }

    public function testListClientTaxIdentifiersReturnsTheListEnvelope(): void
    {
        $this->setActiveScopes([McpScope::Read->value]);

        $client = ClientFactory::createOne(['company' => $this->company]);

        $empty = $this->tool(ClientReadTools::class)->listTaxIdentifiers($client->getId()->toBase58());

        self::assertSame([], $empty->results);
        self::assertSame(1, $empty->page);
        self::assertSame(0, $empty->total);
    }

    /**
     * An invoice whose total and balance are $minorUnits: the save listener recalculates both from its lines.
     */
    private function invoiceOf(Client $client, InvoiceStatus $status, int $minorUnits, ?string $currency = null): Invoice
    {
        return InvoiceFactory::new(['company' => $this->company, 'client' => $client, 'status' => $status, 'currencyCode' => $currency, 'discount' => new Discount()])
            ->afterInstantiate(static function (Invoice $invoice) use ($minorUnits): void {
                $invoice->addLine(new Line()->setQty(1)->setPrice($minorUnits));
            })
            ->create();
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return T
     */
    private function tool(string $class): object
    {
        $tool = self::getContainer()->get($class);
        self::assertInstanceOf($class, $tool);

        return $tool;
    }

    /**
     * @param list<string> $scopes
     */
    private function setActiveScopes(array $scopes): void
    {
        $container = self::getContainer();

        $stack = $container->get(RequestStack::class);
        self::assertInstanceOf(RequestStack::class, $stack);

        // Clear any existing request to avoid leftover scope state
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
