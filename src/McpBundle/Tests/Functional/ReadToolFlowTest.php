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

use DateTimeImmutable;
use Mcp\Exception\ToolCallException;
use SolidInvoice\ClientBundle\Mcp\ClientReadTools;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\DashboardBundle\Mcp\AnalyticsTools;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Mcp\InvoiceReadTools;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use SolidInvoice\McpBundle\Mcp\Tool\ResourceQueryTools;
use SolidInvoice\McpBundle\Security\McpOAuthAuthenticator;
use SolidInvoice\McpBundle\Security\McpScope;
use SolidInvoice\SettingsBundle\Mcp\SettingsReadTools;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Zenstruck\Foundry\Test\Factories;

/**
 * Exercises the Phase 2 read tools end-to-end within the kernel, simulating
 * the scopes a real OAuth-authenticated request would carry.
 *
 * @group functional
 */
final class ReadToolFlowTest extends KernelTestCase
{
    use EnsureApplicationInstalled;
    use Factories;

    public function testCompanyInfoToolReturnsActiveCompany(): void
    {
        $this->setActiveScopes([McpScope::Read->value]);

        $tool = self::getContainer()->get(SettingsReadTools::class);
        self::assertInstanceOf(SettingsReadTools::class, $tool);

        $result = $tool->getCompanyInfo();

        self::assertSame($this->company->getId()->toRfc4122(), $result['id']);
        self::assertSame('SolidInvoice', $result['name']);
    }

    public function testGetResourceReturnsSingleInvoice(): void
    {
        $this->setActiveScopes([McpScope::Read->value]);

        $client = ClientFactory::createOne(['company' => $this->company]);
        $invoice = InvoiceFactory::createOne([
            'company' => $this->company,
            'client' => $client,
            'status' => InvoiceStatus::Pending,
        ]);

        $tool = self::getContainer()->get(ResourceQueryTools::class);
        self::assertInstanceOf(ResourceQueryTools::class, $tool);

        $result = $tool->getResource('invoice', $invoice->getId()->toRfc4122());

        self::assertSame($invoice->getId()->toRfc4122(), $result['id']);
        self::assertSame('pending', $result['status']);
    }

    public function testListOverdueInvoicesReturnsOverdueOnly(): void
    {
        $this->setActiveScopes([McpScope::Read->value]);

        $client = ClientFactory::createOne(['company' => $this->company]);
        InvoiceFactory::createOne([
            'company' => $this->company,
            'client' => $client,
            'status' => InvoiceStatus::Overdue,
            'due' => new DateTimeImmutable('-10 days'),
        ]);
        InvoiceFactory::createOne([
            'company' => $this->company,
            'client' => $client,
            'status' => InvoiceStatus::Pending,
            'due' => new DateTimeImmutable('+10 days'),
        ]);

        $tool = self::getContainer()->get(InvoiceReadTools::class);
        self::assertInstanceOf(InvoiceReadTools::class, $tool);

        $result = $tool->listOverdueInvoices();

        self::assertSame(1, $result['count']);
        self::assertCount(1, $result['results']);
        self::assertSame('overdue', $result['results'][0]['status']);
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
        $this->expectExceptionMessage('mcp:read');

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

        $summary = $tool->getClientSummary($client->getId()->toRfc4122());

        self::assertSame($client->getId()->toRfc4122(), $summary['client']['id']);
        self::assertArrayHasKey('counts_by_status', $summary);
        self::assertArrayHasKey('total_invoiced_amount', $summary);
        self::assertArrayHasKey('outstanding_amount', $summary);
        self::assertArrayHasKey('total_paid_amount', $summary);
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
