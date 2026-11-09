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
use SolidInvoice\ClientBundle\Mcp\ClientReadTools;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\ClientBundle\Test\Factory\ContactFactory;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\DashboardBundle\Mcp\AnalyticsTools;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Enum\RecurringInvoiceStatus;
use SolidInvoice\InvoiceBundle\Mcp\InvoiceReadTools;
use SolidInvoice\InvoiceBundle\Mcp\RecurringInvoiceReadTools;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use SolidInvoice\InvoiceBundle\Test\Factory\RecurringInvoiceFactory;
use SolidInvoice\McpBundle\Mcp\Output\Entity\ClientOutput;
use SolidInvoice\McpBundle\Mcp\Output\Entity\ContactOutput;
use SolidInvoice\McpBundle\Mcp\Output\Entity\InvoiceOutput;
use SolidInvoice\McpBundle\Mcp\Output\Entity\PaymentMethodOutput;
use SolidInvoice\McpBundle\Mcp\Output\Entity\PaymentOutput;
use SolidInvoice\McpBundle\Mcp\Output\Entity\QuoteOutput;
use SolidInvoice\McpBundle\Mcp\Output\Entity\RecurringInvoiceOutput;
use SolidInvoice\McpBundle\Mcp\Output\Entity\TaxOutput;
use SolidInvoice\McpBundle\Security\McpOAuthAuthenticator;
use SolidInvoice\McpBundle\Security\McpScope;
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

    public function testDashboardStatsReturnsStructuredTotals(): void
    {
        $this->setActiveScopes([McpScope::Read->value]);

        $client = ClientFactory::createOne(['company' => $this->company]);
        InvoiceFactory::createOne([
            'company' => $this->company,
            'client' => $client,
            'status' => InvoiceStatus::Pending,
        ]);

        $tool = self::getContainer()->get(AnalyticsTools::class);
        self::assertInstanceOf(AnalyticsTools::class, $tool);

        $stats = $tool->getDashboardStats();

        self::assertArrayHasKey('outstanding', $stats);
        self::assertArrayHasKey('overdue', $stats);
        self::assertArrayHasKey('counts_by_status', $stats);
        self::assertArrayHasKey('total_invoices', $stats);
        self::assertGreaterThanOrEqual(1, $stats['total_invoices']);
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

    public function testClientSummaryIsCompanyScoped(): void
    {
        $this->setActiveScopes([McpScope::Read->value]);

        $client = ClientFactory::createOne(['company' => $this->company]);
        InvoiceFactory::createOne([
            'company' => $this->company,
            'client' => $client,
            'status' => InvoiceStatus::Pending,
        ]);

        $tool = self::getContainer()->get(ClientReadTools::class);
        self::assertInstanceOf(ClientReadTools::class, $tool);

        $summary = $tool->getClientSummary($client->getId()->toBase58());

        self::assertSame($client->getId()->toBase58(), $summary['client']['id']);
        self::assertArrayHasKey('counts_by_status', $summary);
        self::assertArrayHasKey('total_invoiced_amount', $summary);
        self::assertArrayHasKey('outstanding_amount', $summary);
        self::assertArrayHasKey('total_paid_amount', $summary);
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
