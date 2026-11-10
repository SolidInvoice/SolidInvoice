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

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Closure;
use Mcp\Capability\Discovery\SchemaValidator;
use Mcp\Capability\Registry;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Mcp\ClientReadTools;
use SolidInvoice\ClientBundle\Mcp\ClientWriteTools;
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
use SolidInvoice\InvoiceBundle\Mcp\InvoiceWriteTools;
use SolidInvoice\InvoiceBundle\Mcp\RecurringInvoiceReadTools;
use SolidInvoice\InvoiceBundle\Mcp\RecurringInvoiceWriteTools;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use SolidInvoice\InvoiceBundle\Test\Factory\RecurringInvoiceFactory;
use SolidInvoice\McpBundle\Mcp\Tool\WorkflowTools;
use SolidInvoice\McpBundle\Security\McpOAuthAuthenticator;
use SolidInvoice\McpBundle\Security\McpScope;
use SolidInvoice\PaymentBundle\Enum\PaymentStatus;
use SolidInvoice\PaymentBundle\Mcp\PaymentMethodReadTools;
use SolidInvoice\PaymentBundle\Mcp\PaymentReadTools;
use SolidInvoice\PaymentBundle\Mcp\PaymentWriteTools;
use SolidInvoice\PaymentBundle\Test\Factory\PaymentFactory;
use SolidInvoice\PaymentBundle\Test\Factory\PaymentMethodFactory;
use SolidInvoice\ProductBundle\Mcp\ProductReadTools;
use SolidInvoice\ProductBundle\Mcp\ProductWriteTools;
use SolidInvoice\ProductBundle\Test\Factory\ProductFactory;
use SolidInvoice\QuoteBundle\Entity\Line as QuoteLine;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Enum\QuoteStatus;
use SolidInvoice\QuoteBundle\Mcp\QuoteReadTools;
use SolidInvoice\QuoteBundle\Mcp\QuoteWriteTools;
use SolidInvoice\QuoteBundle\Test\Factory\QuoteFactory;
use SolidInvoice\SettingsBundle\Mcp\SettingsReadTools;
use SolidInvoice\TaxBundle\Entity\Tax;
use SolidInvoice\TaxBundle\Mcp\TaxReadTools;
use SolidInvoice\TaxBundle\Mcp\TaxWriteTools;
use SolidInvoice\TaxBundle\Test\Factory\TaxFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use function json_decode;
use function json_encode;

/**
 * Calls every tool's handler and validates the result against the tool's published outputSchema,
 * converted the way the SDK's CallToolHandler does it. Each case builds its own fixtures, so order
 * does not matter. The mailer is the null:// transport in the test environment, so the tools that
 * send email send nothing.
 */
#[CoversNothing]
#[Group('functional')]
final class ToolOutputConformanceTest extends KernelTestCase
{
    use EnsureApplicationInstalled;

    public function testEveryRegisteredToolHasACase(): void
    {
        $covered = [];
        foreach (self::cases() as $case) {
            $covered[$case[0]] = true;
        }

        $covered = array_keys($covered);
        $registered = array_keys($this->registry()->getTools()->references);
        sort($covered);
        sort($registered);

        self::assertSame($registered, $covered, 'Add a conformance case for every new tool, and drop removed ones.');
    }

    /**
     * @param Closure(self): array{class-string, string, array<string, mixed>} $case
     */
    #[DataProvider('cases')]
    public function testResultConformsToOutputSchema(string $toolName, Closure $case): void
    {
        $this->setActiveScopes([McpScope::Read->value, McpScope::Write->value]);

        [$class, $method, $arguments] = $case($this);

        $handler = self::getContainer()->get($class);
        self::assertIsObject($handler);

        $result = $handler->{$method}(...$arguments);

        $tool = $this->registry()->getTool($toolName);
        self::assertNotNull($tool->tool->outputSchema);

        $sent = json_decode(json_encode($tool->extractStructuredContent($result), JSON_THROW_ON_ERROR), false, 512, JSON_THROW_ON_ERROR);

        self::assertSame([], new SchemaValidator()->validateAgainstJsonSchema($sent, $tool->tool->outputSchema));
    }

    public function testAnEmptyListEncodesResultsAsAJsonArray(): void
    {
        $this->setActiveScopes([McpScope::Read->value]);

        $result = self::getContainer()->get(ClientReadTools::class)->listClients();

        self::assertStringContainsString('"results":[]', json_encode($result, JSON_THROW_ON_ERROR));
    }

    /**
     * @return iterable<string, array{string, Closure(self): array{class-string, string, array<string, mixed>}}>
     */
    public static function cases(): iterable
    {
        // Read tools.
        yield 'get_company_info' => ['get_company_info', static fn (self $t): array => [SettingsReadTools::class, 'getCompanyInfo', []]];
        yield 'get_client' => ['get_client', static fn (self $t): array => [ClientReadTools::class, 'getClient', ['id' => $t->client()->getId()->toBase58()]]];
        yield 'list_clients' => ['list_clients', static function (self $t): array {
            $t->client();

            return [
                ClientReadTools::class,
                'listClients',
                [],
            ];
        }];
        yield 'list_clients (no clients)' => ['list_clients', static fn (self $t): array => [ClientReadTools::class, 'listClients', []]];
        yield 'get_contact' => ['get_contact', static fn (self $t): array => [ClientReadTools::class, 'getContact', ['id' => ContactFactory::createOne(['company' => $t->company, 'client' => $t->client()])->getId()->toBase58()]]];
        yield 'list_contacts' => ['list_contacts', static function (self $t): array {
            $client = $t->client();
            ContactFactory::createOne(['company' => $t->company, 'client' => $client]);

            return [
                ClientReadTools::class,
                'listContacts',
                ['client_id' => $client->getId()->toBase58()],
            ];
        }];
        yield 'get_client_summary' => ['get_client_summary', static fn (self $t): array => [ClientReadTools::class, 'getClientSummary', ['client_id' => $t->invoice()->getClient()?->getId()->toBase58()]]];
        yield 'list_client_tax_identifiers' => ['list_client_tax_identifiers', static function (self $t): array {
            $client = $t->client();
            self::getContainer()->get(ClientWriteTools::class)->addTaxIdentifier($client->getId()->toBase58(), 'VAT', 'GB123');

            return [
                ClientReadTools::class,
                'listTaxIdentifiers',
                ['client_id' => $client->getId()->toBase58()],
            ];
        }];
        yield 'get_invoice' => ['get_invoice', static fn (self $t): array => [InvoiceReadTools::class, 'getInvoice', ['id' => $t->invoice()->getId()->toBase58()]]];
        yield 'get_invoice (every nullable relation null)' => ['get_invoice', static fn (self $t): array => [InvoiceReadTools::class, 'getInvoice', ['id' => $t->invoice(bare: true)->getId()->toBase58()]]];
        yield 'list_invoices' => ['list_invoices', static function (self $t): array {
            $t->invoice();
            $t->invoice(bare: true);

            return [
                InvoiceReadTools::class,
                'listInvoices',
                [],
            ];
        }];
        yield 'get_quote' => ['get_quote', static fn (self $t): array => [QuoteReadTools::class, 'getQuote', ['id' => $t->quote()->getId()->toBase58()]]];
        yield 'get_quote (every nullable relation null)' => ['get_quote', static fn (self $t): array => [QuoteReadTools::class, 'getQuote', ['id' => $t->quote(bare: true)->getId()->toBase58()]]];
        yield 'list_quotes' => ['list_quotes', static function (self $t): array {
            $t->quote();
            $t->quote(bare: true);

            return [
                QuoteReadTools::class,
                'listQuotes',
                [],
            ];
        }];
        yield 'get_recurring_invoice' => ['get_recurring_invoice', static fn (self $t): array => [RecurringInvoiceReadTools::class, 'getRecurringInvoice', ['id' => $t->recurring()->getId()->toBase58()]]];
        yield 'list_recurring_invoices' => ['list_recurring_invoices', static function (self $t): array {
            $t->recurring();

            return [
                RecurringInvoiceReadTools::class,
                'listRecurringInvoices',
                [],
            ];
        }];
        yield 'get_payment' => ['get_payment', static fn (self $t): array => [PaymentReadTools::class, 'getPayment', ['id' => $t->payment()->getId()->toBase58()]]];
        yield 'get_payment (no method)' => ['get_payment', static fn (self $t): array => [PaymentReadTools::class, 'getPayment', ['id' => $t->payment(withMethod: false)->getId()->toBase58()]]];
        yield 'list_payments' => ['list_payments', static function (self $t): array {
            $t->payment();
            $t->payment(withMethod: false);

            return [
                PaymentReadTools::class,
                'listPayments',
                [],
            ];
        }];
        yield 'get_payment_method' => ['get_payment_method', static fn (self $t): array => [PaymentMethodReadTools::class, 'getPaymentMethod', ['id' => PaymentMethodFactory::createOne(['company' => $t->company])->getId()->toBase58()]]];
        yield 'list_payment_methods' => ['list_payment_methods', static function (self $t): array {
            PaymentMethodFactory::createOne(['company' => $t->company]);

            return [
                PaymentMethodReadTools::class,
                'listPaymentMethods',
                [],
            ];
        }];
        yield 'get_tax_rate' => ['get_tax_rate', static fn (self $t): array => [TaxReadTools::class, 'getTaxRate', ['id' => TaxFactory::createOne(['company' => $t->company])->getId()->toBase58()]]];
        yield 'list_tax_rates' => ['list_tax_rates', static function (self $t): array {
            TaxFactory::createOne(['company' => $t->company]);

            return [
                TaxReadTools::class,
                'listTaxRates',
                [],
            ];
        }];
        yield 'get_product' => ['get_product', static fn (self $t): array => [ProductReadTools::class, 'getProduct', ['product_id' => ProductFactory::createOne(['company' => $t->company])->getId()->toBase58()]]];
        yield 'list_products' => ['list_products', static function (self $t): array {
            ProductFactory::createOne(['company' => $t->company, 'tax' => TaxFactory::createOne(['company' => $t->company])]);

            return [
                ProductReadTools::class,
                'listProducts',
                [],
            ];
        }];
        yield 'list_workflow_transitions' => ['list_workflow_transitions', static fn (self $t): array => [WorkflowTools::class, 'listWorkflowTransitions', ['resource' => 'invoice', 'id' => $t->invoice()->getId()->toBase58()]]];
        yield 'get_dashboard_stats' => ['get_dashboard_stats', static function (self $t): array {
            $t->payment();

            return [
                AnalyticsTools::class,
                'getDashboardStats',
                [],
            ];
        }];
        yield 'get_dashboard_stats (empty company)' => ['get_dashboard_stats', static fn (self $t): array => [AnalyticsTools::class, 'getDashboardStats', []]];
        yield 'get_total_outstanding' => ['get_total_outstanding', static function (self $t): array {
            $t->invoice();

            return [
                AnalyticsTools::class,
                'getTotalOutstanding',
                [],
            ];
        }];
        yield 'get_total_overdue' => ['get_total_overdue', static function (self $t): array {
            $t->invoice(status: InvoiceStatus::Overdue);

            return [
                AnalyticsTools::class,
                'getTotalOverdue',
                [],
            ];
        }];
        yield 'get_invoice_status_distribution' => ['get_invoice_status_distribution', static function (self $t): array {
            $t->invoice();

            return [
                AnalyticsTools::class,
                'getInvoiceStatusDistribution',
                [],
            ];
        }];
        yield 'get_revenue_by_period' => ['get_revenue_by_period', static function (self $t): array {
            $t->payment();

            return [
                AnalyticsTools::class,
                'getRevenueByPeriod',
                ['start_date' => '2000-01-01', 'end_date' => '2100-01-01', 'group_by' => 'month'],
            ];
        }];

        // Write tools.
        yield 'create_client' => ['create_client', static fn (self $t): array => [ClientWriteTools::class, 'createClient', ['name' => 'Acme', 'currency_code' => 'USD', 'website' => 'https://acme.example']]];
        yield 'update_client' => ['update_client', static fn (self $t): array => [ClientWriteTools::class, 'updateClient', ['id' => $t->client()->getId()->toBase58(), 'name' => 'Renamed']]];
        yield 'delete_client' => ['delete_client', static fn (self $t): array => [ClientWriteTools::class, 'deleteClient', ['id' => $t->client()->getId()->toBase58()]]];
        yield 'create_contact' => ['create_contact', static fn (self $t): array => [ClientWriteTools::class, 'createContact', ['client_id' => $t->client()->getId()->toBase58(), 'email' => 'jane@example.com', 'first_name' => 'Jane']]];
        yield 'update_contact' => ['update_contact', static fn (self $t): array => [ClientWriteTools::class, 'updateContact', ['id' => ContactFactory::createOne(['company' => $t->company, 'client' => $t->client()])->getId()->toBase58(), 'last_name' => 'Smith']]];
        yield 'delete_contact' => ['delete_contact', static fn (self $t): array => [ClientWriteTools::class, 'deleteContact', ['id' => ContactFactory::createOne(['company' => $t->company, 'client' => $t->client()])->getId()->toBase58()]]];
        yield 'add_client_tax_identifier' => ['add_client_tax_identifier', static fn (self $t): array => [ClientWriteTools::class, 'addTaxIdentifier', ['client_id' => $t->client()->getId()->toBase58(), 'label' => 'VAT', 'value' => 'GB123', 'primary' => true]]];
        yield 'remove_client_tax_identifier' => ['remove_client_tax_identifier', static function (self $t): array {
            $identifier = self::getContainer()->get(ClientWriteTools::class)->addTaxIdentifier($t->client()->getId()->toBase58(), 'VAT', 'GB123');

            return [
                ClientWriteTools::class,
                'removeTaxIdentifier',
                ['tax_identifier_id' => $identifier->id],
            ];
        }];
        yield 'create_invoice' => ['create_invoice', static fn (self $t): array => [InvoiceWriteTools::class, 'createInvoice', [
            'client_id' => $t->client()->getId()->toBase58(),
            'lines' => [['description' => 'Work', 'price' => '12.50', 'qty' => 2]],
            'due' => '2030-01-31',
            'discount_type' => 'percentage',
            'discount_value' => '10',
            'terms' => 'Net 30',
            'notes' => 'Thanks',
        ]]];
        yield 'create_invoice (minimal)' => ['create_invoice', static fn (self $t): array => [InvoiceWriteTools::class, 'createInvoice', [
            'client_id' => $t->client()->getId()->toBase58(),
            'lines' => [['description' => 'Work', 'price' => '12.50', 'qty' => 1]],
        ]]];
        yield 'apply_invoice_transition' => ['apply_invoice_transition', static fn (self $t): array => [InvoiceWriteTools::class, 'applyInvoiceTransition', ['invoice_id' => $t->invoice()->getId()->toBase58(), 'transition' => 'cancel']]];
        yield 'clone_invoice' => ['clone_invoice', static fn (self $t): array => [InvoiceWriteTools::class, 'cloneInvoice', ['invoice_id' => $t->invoice()->getId()->toBase58()]]];
        yield 'send_invoice_reminder' => ['send_invoice_reminder', static function (self $t): array {
            $invoice = $t->invoice();
            $invoice->addUser(ContactFactory::createOne(['company' => $t->company, 'client' => $invoice->getClient()]));

            return [
                InvoiceWriteTools::class,
                'sendInvoiceReminder',
                ['invoice_id' => $invoice->getId()->toBase58()],
            ];
        }];
        yield 'create_recurring_invoice' => ['create_recurring_invoice', static fn (self $t): array => [RecurringInvoiceWriteTools::class, 'createRecurringInvoice', [
            'client_id' => $t->client()->getId()->toBase58(),
            'lines' => [['description' => 'Retainer', 'price' => '2500', 'qty' => 1]],
            'date_start' => '2030-05-01',
            'schedule' => ['type' => 'monthly', 'end_type' => 'after', 'end_occurrence' => 12],
        ]]];
        yield 'apply_recurring_transition' => ['apply_recurring_transition', static fn (self $t): array => [InvoiceWriteTools::class, 'applyRecurringTransition', ['recurring_id' => $t->recurring()->getId()->toBase58(), 'transition' => 'activate']]];
        yield 'create_quote' => ['create_quote', static fn (self $t): array => [QuoteWriteTools::class, 'createQuote', [
            'client_id' => $t->client()->getId()->toBase58(),
            'lines' => [['description' => 'Work', 'price' => '12.50', 'qty' => 2]],
            'due' => '2030-01-31',
        ]]];
        yield 'create_quote (no due date)' => ['create_quote', static fn (self $t): array => [QuoteWriteTools::class, 'createQuote', [
            'client_id' => $t->client()->getId()->toBase58(),
            'lines' => [['description' => 'Work', 'price' => '12.50', 'qty' => 1]],
        ]]];
        yield 'apply_quote_transition' => ['apply_quote_transition', static fn (self $t): array => [QuoteWriteTools::class, 'applyQuoteTransition', ['quote_id' => $t->quote(status: QuoteStatus::Pending)->getId()->toBase58(), 'transition' => 'accept']]];
        yield 'clone_quote' => ['clone_quote', static fn (self $t): array => [QuoteWriteTools::class, 'cloneQuote', ['quote_id' => $t->quote()->getId()->toBase58()]]];
        yield 'convert_quote_to_invoice' => ['convert_quote_to_invoice', static fn (self $t): array => [QuoteWriteTools::class, 'convertQuoteToInvoice', ['quote_id' => $t->quote(status: QuoteStatus::Accepted)->getId()->toBase58()]]];
        yield 'record_payment' => ['record_payment', static fn (self $t): array => [PaymentWriteTools::class, 'recordPayment', [
            'invoice_id' => $t->invoice()->getId()->toBase58(),
            'amount' => '12.50',
            'currency' => 'USD',
            'method' => 'bank_transfer',
            'reference' => 'REF-1',
            'paid_at' => Carbon::now()->format('Y-m-d'),
        ]]];
        yield 'record_payment (no optional fields)' => ['record_payment', static fn (self $t): array => [PaymentWriteTools::class, 'recordPayment', [
            'invoice_id' => $t->invoice()->getId()->toBase58(),
            'amount' => '12.50',
            'currency' => 'USD',
        ]]];
        yield 'create_tax_rate' => ['create_tax_rate', static fn (self $t): array => [TaxWriteTools::class, 'createTaxRate', ['name' => 'VAT', 'rate' => 20.0, 'type' => Tax::TYPE_EXCLUSIVE]]];
        yield 'update_tax_rate' => ['update_tax_rate', static fn (self $t): array => [TaxWriteTools::class, 'updateTaxRate', ['tax_id' => TaxFactory::createOne(['company' => $t->company])->getId()->toBase58(), 'name' => 'Renamed']]];
        yield 'delete_tax_rate' => ['delete_tax_rate', static fn (self $t): array => [TaxWriteTools::class, 'deleteTaxRate', ['id' => TaxFactory::createOne(['company' => $t->company])->getId()->toBase58()]]];
        yield 'create_product' => ['create_product', static fn (self $t): array => [ProductWriteTools::class, 'createProduct', ['name' => 'Widget', 'price' => '49.99', 'tax_id' => TaxFactory::createOne(['company' => $t->company])->getId()->toBase58()]]];
        yield 'create_product (no optional fields)' => ['create_product', static fn (self $t): array => [ProductWriteTools::class, 'createProduct', ['name' => 'Widget', 'price' => '49.99']]];
        yield 'update_product' => ['update_product', static fn (self $t): array => [ProductWriteTools::class, 'updateProduct', ['product_id' => ProductFactory::createOne(['company' => $t->company])->getId()->toBase58(), 'name' => 'Renamed']]];
        yield 'delete_product' => ['delete_product', static fn (self $t): array => [ProductWriteTools::class, 'deleteProduct', ['product_id' => ProductFactory::createOne(['company' => $t->company])->getId()->toBase58()]]];
    }

    private function client(): Client
    {
        return ClientFactory::createOne(['company' => $this->company, 'currencyCode' => 'USD']);
    }

    /**
     * A pending, payable invoice with one line. $bare leaves every optional relation empty: no due
     * date, discount, taxes or contacts.
     */
    private function invoice(bool $bare = false, InvoiceStatus $status = InvoiceStatus::Pending): Invoice
    {
        return InvoiceFactory::new([
            'company' => $this->company,
            'client' => $this->client(),
            'status' => $status,
            'paidDate' => null,
            'discount' => new Discount(),
            ...($bare ? ['due' => null, 'terms' => null, 'notes' => null] : ['due' => CarbonImmutable::now()->addDays(30)]),
        ])
            ->afterInstantiate(static function (Invoice $invoice): void {
                $invoice->addLine(new Line()->setQty(1)->setPrice(100000));
            })
            ->create();
    }

    private function quote(bool $bare = false, QuoteStatus $status = QuoteStatus::Draft): Quote
    {
        return QuoteFactory::new([
            'company' => $this->company,
            'client' => $this->client(),
            'status' => $status,
            'discount' => new Discount(),
            ...($bare ? ['due' => null, 'terms' => null, 'notes' => null] : []),
        ])
            ->afterInstantiate(static function (Quote $quote): void {
                $quote->addLine(new QuoteLine()->setQty(1)->setPrice(100000));
            })
            ->create();
    }

    private function recurring(): object
    {
        return RecurringInvoiceFactory::createOne([
            'company' => $this->company,
            'client' => $this->client(),
            'status' => RecurringInvoiceStatus::Draft,
        ]);
    }

    private function payment(bool $withMethod = true): object
    {
        $client = $this->client();

        return PaymentFactory::createOne([
            'company' => $this->company,
            'client' => $client,
            'invoice' => $this->invoice(),
            'status' => PaymentStatus::Captured,
            'totalAmount' => 1250,
            'currencyCode' => 'USD',
            ...($withMethod ? ['method' => PaymentMethodFactory::createOne(['company' => $this->company])] : []),
        ]);
    }

    private function registry(): Registry
    {
        // Building the server is what loads the builder's addTool() calls into the registry.
        self::getContainer()->get('mcp.server.solidinvoice');
        $registry = self::getContainer()->get('mcp.server.solidinvoice.registry');
        self::assertInstanceOf(Registry::class, $registry);

        return $registry;
    }

    /**
     * @param list<string> $scopes
     */
    private function setActiveScopes(array $scopes): void
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
