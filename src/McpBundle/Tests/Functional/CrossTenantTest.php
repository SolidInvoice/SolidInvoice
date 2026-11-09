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
use Closure;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Mcp\Exception\ToolCallException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Enum\ClientStatus;
use SolidInvoice\ClientBundle\Mcp\ClientReadTools;
use SolidInvoice\ClientBundle\Mcp\ClientWriteTools;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Mcp\InvoiceReadTools;
use SolidInvoice\InvoiceBundle\Mcp\InvoiceWriteTools;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use SolidInvoice\McpBundle\Mcp\Output\Entity\InvoiceOutput;
use SolidInvoice\McpBundle\Security\McpOAuthAuthenticator;
use SolidInvoice\McpBundle\Security\McpScope;
use SolidInvoice\PaymentBundle\Mcp\PaymentReadTools;
use SolidInvoice\PaymentBundle\Mcp\PaymentWriteTools;
use SolidInvoice\ProductBundle\Mcp\ProductReadTools;
use SolidInvoice\ProductBundle\Test\Factory\ProductFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Verifies that a token bound to company A cannot read or write records
 * belonging to company B. The CompanyFilter and write-path company overrides
 * make this invariant hold.
 */
#[Group('functional')]
final class CrossTenantTest extends KernelTestCase
{
    use EnsureApplicationInstalled;

    /**
     * @var array{company: Company, client: Client, invoice: Invoice}|null
     */
    private ?array $foreign = null;

    public function testListInvoicesHidesOtherCompanyInvoices(): void
    {
        $this->seedForeignTenant();

        // Invoice in the active company (created after the filter is reset).
        InvoiceFactory::createOne([
            'company' => $this->company,
            'client' => ClientFactory::createOne(['company' => $this->company, 'name' => 'Our Client']),
            'status' => InvoiceStatus::Pending,
        ]);

        $this->activateToken(McpScope::Read);

        $tool = self::getContainer()->get(InvoiceReadTools::class);
        self::assertInstanceOf(InvoiceReadTools::class, $tool);

        $result = $tool->listInvoices();

        self::assertSame(1, $result->total, 'CompanyFilter should hide foreign-company invoices.');
        self::assertCount(1, $result->results);
        $invoice = $result->results[0];
        self::assertInstanceOf(InvoiceOutput::class, $invoice);
        self::assertSame('Our Client', $invoice->client?->name);
    }

    /**
     * @param Closure(self, string, string): mixed $call receives the test, the foreign client id and the foreign invoice id
     */
    #[DataProvider('foreignRecordCalls')]
    public function testForeignRecordIsNotFound(McpScope $scope, Closure $call): void
    {
        $this->seedForeignTenant();
        self::assertNotNull($this->foreign);
        $clientId = $this->foreign['client']->getId()?->toBase58();
        $invoiceId = $this->foreign['invoice']->getId()?->toBase58();
        self::assertNotNull($clientId);
        self::assertNotNull($invoiceId);

        $this->activateToken($scope);

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessageIsOrContains('not found');

        $call($this, $clientId, $invoiceId);
    }

    /**
     * @return iterable<string, array{McpScope, Closure(self, string, string): mixed}>
     */
    public static function foreignRecordCalls(): iterable
    {
        yield 'get_invoice' => [McpScope::Read, static fn (self $test, string $client, string $invoice): mixed => $test->tool(InvoiceReadTools::class)->getInvoice($invoice)];
        yield 'list_invoices by client' => [McpScope::Read, static fn (self $test, string $client): mixed => $test->tool(InvoiceReadTools::class)->listInvoices(client_id: $client)];
        yield 'list_payments by invoice' => [McpScope::Read, static fn (self $test, string $client, string $invoice): mixed => $test->tool(PaymentReadTools::class)->listPayments(invoice_id: $invoice)];
        yield 'get_client' => [McpScope::Read, static fn (self $test, string $client): mixed => $test->tool(ClientReadTools::class)->getClient($client)];
        yield 'list_contacts by client' => [McpScope::Read, static fn (self $test, string $client): mixed => $test->tool(ClientReadTools::class)->listContacts(client_id: $client)];
        yield 'update_client' => [McpScope::Write, static fn (self $test, string $client): mixed => $test->tool(ClientWriteTools::class)->updateClient($client, name: 'Hijacked')];
        yield 'delete_client' => [McpScope::Write, static fn (self $test, string $client): mixed => $test->tool(ClientWriteTools::class)->deleteClient($client)];
        yield 'create_contact' => [McpScope::Write, static fn (self $test, string $client): mixed => $test->tool(ClientWriteTools::class)->createContact($client, 'x@example.com', 'X')];
        yield 'record_payment' => [McpScope::Write, static fn (self $test, string $client, string $invoice): mixed => $test->tool(PaymentWriteTools::class)->recordPayment($invoice, '1.00', 'EUR')];
    }

    public function testInvoiceTransitionOnForeignInvoiceFails(): void
    {
        $this->seedForeignTenant();
        self::assertNotNull($this->foreign);
        $foreignId = $this->foreign['invoice']->getId()?->toBase58();
        self::assertNotNull($foreignId);

        $this->activateToken(McpScope::Write);

        $tool = self::getContainer()->get(InvoiceWriteTools::class);
        self::assertInstanceOf(InvoiceWriteTools::class, $tool);

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessageIsOrContains('not found');

        $tool->applyInvoiceTransition($foreignId, 'pay');
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return T
     */
    public function tool(string $class): object
    {
        $tool = self::getContainer()->get($class);
        self::assertInstanceOf($class, $tool);

        return $tool;
    }

    public function testGetProductReturnsNotFoundForOtherCompanyProduct(): void
    {
        $this->seedForeignTenant();
        self::assertNotNull($this->foreign);

        $em = self::getContainer()->get(EntityManagerInterface::class);
        $em->getFilters()->disable('company');
        $product = ProductFactory::createOne(['company' => $this->foreign['company']]);
        $foreignId = $product->getId()?->toRfc4122();
        $em->clear();
        self::getContainer()->get(CompanySelector::class)->switchCompany($this->company->getId());
        self::assertNotNull($foreignId);

        $this->activateToken(McpScope::Read);

        $tool = self::getContainer()->get(ProductReadTools::class);
        self::assertInstanceOf(ProductReadTools::class, $tool);

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessageIsOrContains('not found');

        $tool->getProduct($foreignId);
    }

    private function seedForeignTenant(): void
    {
        if ($this->foreign !== null) {
            return;
        }

        $registry = self::getContainer()->get('doctrine');
        self::assertInstanceOf(ManagerRegistry::class, $registry);

        $em = $registry->getManager();
        self::assertInstanceOf(EntityManagerInterface::class, $em);

        // Disable the company filter so we can persist into a different tenant.
        $filters = $em->getFilters();
        $wasEnabled = $filters->isEnabled('company');

        if ($wasEnabled) {
            $filters->disable('company');
        }

        $company = new Company();
        $company->setName('Foreign Co');
        $company->currency = 'EUR';

        $em->persist($company);
        $em->flush();

        $client = new Client();
        $client->setName('Foreign Client');
        $client->setCurrencyCode('EUR');
        $client->setStatus(ClientStatus::Active);
        $client->setCompany($company);

        $em->persist($client);

        $invoice = new Invoice();
        $invoice->setClient($client);
        $invoice->setStatus(InvoiceStatus::Pending);
        $invoice->setInvoiceId('FOREIGN-001');
        $invoice->setInvoiceDate(CarbonImmutable::now());
        $invoice->setCompany($company);

        $em->persist($invoice);
        $em->flush();

        // Clear the EM identity map so subsequent `find()` calls hit the DB
        // (where the CompanyFilter will kick in) instead of returning cached entities.
        $em->clear();

        // Re-enable the filter for the active (our) company.
        if ($wasEnabled) {
            self::getContainer()->get(CompanySelector::class)->switchCompany($this->company->getId());
        }

        $this->foreign = [
            'company' => $company,
            'client' => $client,
            'invoice' => $invoice,
        ];
    }

    private function activateToken(McpScope $scope): void
    {
        $container = self::getContainer();

        $stack = $container->get(RequestStack::class);
        self::assertInstanceOf(RequestStack::class, $stack);

        while ($stack->getMainRequest() instanceof Request) {
            $stack->pop();
        }

        $request = new Request();
        $request->attributes->set(McpOAuthAuthenticator::ATTR_SCOPES, [$scope->value]);

        $stack->push($request);

        $selector = $container->get(CompanySelector::class);
        self::assertInstanceOf(CompanySelector::class, $selector);
        $selector->switchCompany($this->company->getId());
    }
}
