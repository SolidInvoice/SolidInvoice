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
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use ReflectionMethod;
use ReflectionProperty;
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
use SolidInvoice\InvoiceBundle\Mcp\InvoiceReadTools;
use SolidInvoice\InvoiceBundle\Mcp\RecurringInvoiceReadTools;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use SolidInvoice\InvoiceBundle\Test\Factory\RecurringInvoiceFactory;
use SolidInvoice\McpBundle\Mcp\Output\AmountOutput;
use SolidInvoice\McpBundle\Mcp\Output\Entity\ClientOutput;
use SolidInvoice\McpBundle\Mcp\Output\Entity\InvoiceOutput;
use SolidInvoice\McpBundle\Mcp\Output\Entity\QuoteOutput;
use SolidInvoice\McpBundle\Mcp\Output\Entity\RecurringInvoiceOutput;
use SolidInvoice\McpBundle\Mcp\Output\StatusCountOutput;
use SolidInvoice\McpBundle\Security\McpOAuthAuthenticator;
use SolidInvoice\McpBundle\Security\McpScope;
use SolidInvoice\PaymentBundle\Enum\PaymentStatus;
use SolidInvoice\PaymentBundle\Test\Factory\PaymentFactory;
use SolidInvoice\QuoteBundle\Mcp\QuoteReadTools;
use SolidInvoice\QuoteBundle\Test\Factory\QuoteFactory;
use SolidInvoice\TaxBundle\Mcp\TaxWriteTools;
use SolidInvoice\TaxBundle\Test\Factory\TaxFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Uid\Ulid;

/**
 * a client row with `currency_code IS NULL` resolves the system
 * default currency on every rendered surface, via {@see \SolidInvoice\ClientBundle\Listener\ClientListener::postLoad()}
 * hydrating `Client::$currency`. The MCP read surface must report that same default
 * rather than `null`, so these fixtures are never persisted-and-reused directly: each
 * test clears the entity manager and re-fetches through the get_* tools,
 * so `postLoad` actually runs before normalization.
 */
#[Group('functional')]
final class EntityNormalizerCurrencyDefaultTest extends KernelTestCase
{
    use EnsureApplicationInstalled;

    public function testInvoiceWithNoFrozenCurrencyAndNullClientCurrencyReportsSystemDefault(): void
    {
        $this->activateScopes([McpScope::Read->value]);

        $client = ClientFactory::createOne(['currencyCode' => null, 'company' => $this->company]);
        $invoiceId = InvoiceFactory::createOne([
            'client' => $client,
            'company' => $this->company,
        ])->getId();

        $this->clearEntityManager();
        self::assertInstanceOf(Ulid::class, $invoiceId);

        $result = $this->tool(InvoiceReadTools::class)->getInvoice($invoiceId->toBase58());
        self::assertInstanceOf(InvoiceOutput::class, $result);

        self::assertSame('USD', $result->currency);
        self::assertSame('USD', $result->client?->currency);
    }

    public function testQuoteWithNoFrozenCurrencyAndNullClientCurrencyReportsSystemDefault(): void
    {
        $this->activateScopes([McpScope::Read->value]);

        $client = ClientFactory::createOne(['currencyCode' => null, 'company' => $this->company]);
        $quoteId = QuoteFactory::createOne([
            'client' => $client,
            'company' => $this->company,
        ])->getId();

        $this->clearEntityManager();
        self::assertInstanceOf(Ulid::class, $quoteId);

        $result = $this->tool(QuoteReadTools::class)->getQuote($quoteId->toBase58());
        self::assertInstanceOf(QuoteOutput::class, $result);

        self::assertSame('USD', $result->currency);
    }

    public function testRecurringInvoiceWithNullClientCurrencyReportsSystemDefault(): void
    {
        $this->activateScopes([McpScope::Read->value]);

        $client = ClientFactory::createOne(['currencyCode' => null, 'company' => $this->company]);
        $recurringInvoiceId = RecurringInvoiceFactory::createOne([
            'client' => $client,
            'company' => $this->company,
        ])->getId();

        $this->clearEntityManager();
        self::assertInstanceOf(Ulid::class, $recurringInvoiceId);

        $result = $this->tool(RecurringInvoiceReadTools::class)->getRecurringInvoice($recurringInvoiceId->toBase58());
        self::assertInstanceOf(RecurringInvoiceOutput::class, $result);

        self::assertSame('USD', $result->currency);
    }

    public function testClientWithNullCurrencyReportsSystemDefault(): void
    {
        $this->activateScopes([McpScope::Read->value]);

        $clientId = ClientFactory::createOne(['currencyCode' => null, 'company' => $this->company])->getId();

        $this->clearEntityManager();
        self::assertInstanceOf(Ulid::class, $clientId);

        $result = $this->tool(ClientReadTools::class)->getClient($clientId->toBase58());
        self::assertInstanceOf(ClientOutput::class, $result);

        self::assertSame('USD', $result->currency);
    }

    /**
     * Guards against the defect on this surface: a frozen
     * document must keep reporting the currency it was issued in, not its client's.
     */
    public function testInvoiceWithFrozenCurrencyIgnoresItsClientCurrency(): void
    {
        $this->activateScopes([McpScope::Read->value]);

        $client = ClientFactory::createOne(['currencyCode' => 'EUR', 'company' => $this->company]);
        $invoiceId = InvoiceFactory::createOne([
            'client' => $client,
            'currencyCode' => 'USD',
            'company' => $this->company,
        ])->getId();

        $this->clearEntityManager();
        self::assertInstanceOf(Ulid::class, $invoiceId);

        $result = $this->tool(InvoiceReadTools::class)->getInvoice($invoiceId->toBase58());
        self::assertInstanceOf(InvoiceOutput::class, $result);

        self::assertSame('USD', $result->currency);
    }

    public function testTotalsCountInvoicesAndPaymentsWithNoCurrencyUnderTheSystemDefault(): void
    {
        $this->activateScopes([McpScope::Read->value]);

        $client = ClientFactory::createOne(['currencyCode' => null, 'company' => $this->company]);
        $invoice = InvoiceFactory::new(['company' => $this->company, 'client' => $client, 'status' => InvoiceStatus::Pending, 'currencyCode' => null, 'discount' => new Discount()])
            ->afterInstantiate(static function (Invoice $invoice): void {
                $invoice->addLine(new Line()->setQty(1)->setPrice(10000));
            })
            ->create();
        PaymentFactory::createOne(['company' => $this->company, 'client' => $client, 'invoice' => $invoice, 'status' => PaymentStatus::Captured, 'completed' => CarbonImmutable::parse('2026-03-02 10:00'), 'totalAmount' => 2500, 'currencyCode' => '']);
        $clientId = $client->getId()->toBase58();

        $this->clearEntityManager();

        $summary = $this->tool(ClientReadTools::class)->getClientSummary($clientId);
        self::assertEquals([new AmountOutput('USD', '100.00')], $summary->total_invoiced);
        self::assertEquals([new AmountOutput('USD', '25.00')], $summary->total_paid);

        $analytics = $this->tool(AnalyticsTools::class);
        self::assertEquals([new AmountOutput('USD', '100.00')], $analytics->getTotalOutstanding()->totals);

        $revenue = $analytics->getRevenueByPeriod('2026-03-01', '2026-03-31');
        self::assertEquals([new AmountOutput('USD', '25.00')], $revenue->periods[0]->totals);
    }

    public function testDeletesEchoTheIdAsBase58WhateverFormWasPassed(): void
    {
        $this->activateScopes([McpScope::Write->value]);

        $client = ClientFactory::createOne(['company' => $this->company]);
        $contact = ContactFactory::createOne(['company' => $this->company, 'client' => ClientFactory::createOne(['company' => $this->company])]);
        $tax = TaxFactory::createOne(['company' => $this->company]);

        self::assertSame($client->getId()->toBase58(), $this->tool(ClientWriteTools::class)->deleteClient($this->rfc4122($client->getId()))->id);
        self::assertSame($contact->getId()->toBase58(), $this->tool(ClientWriteTools::class)->deleteContact($this->rfc4122($contact->getId()))->id);
        self::assertSame($tax->getId()->toBase58(), $this->tool(TaxWriteTools::class)->deleteTaxRate($this->rfc4122($tax->getId()))->id);
    }

    public function testRecurringScheduleDaysStayAListWhenTheStoredArrayHasGaps(): void
    {
        $this->activateScopes([McpScope::Read->value]);

        $recurring = RecurringInvoiceFactory::createOne(['client' => ClientFactory::createOne(['company' => $this->company]), 'company' => $this->company]);
        $recurring->getRecurringOptions()->setDays([1, 3, 5]);
        $reflection = new ReflectionProperty($recurring->getRecurringOptions(), 'days');
        $reflection->setValue($recurring->getRecurringOptions(), [0 => 1, 2 => 3, 5 => 5]);

        $result = $this->tool(RecurringInvoiceReadTools::class)->getRecurringInvoice($recurring->getId()->toBase58());
        self::assertInstanceOf(RecurringInvoiceOutput::class, $result);

        self::assertSame([1, 3, 5], $result->schedule->days);
    }

    /**
     * The status column is NOT NULL in a fresh schema, so a NULL-status row (legacy data) is fed in as
     * the empty key PHP gives a NULL array key.
     */
    public function testStatusCountsSkipRowsWithoutAKnownStatus(): void
    {
        $method = new ReflectionMethod(AnalyticsTools::class, 'statusCounts');

        $result = $method->invoke($this->tool(AnalyticsTools::class), ['' => 2, 'pending' => 1]);

        self::assertEquals([new StatusCountOutput(InvoiceStatus::Pending, 1)], $result);
    }

    private function rfc4122(Ulid $id): string
    {
        $hex = bin2hex($id->toBinary());

        return sprintf('%s-%s-%s-%s-%s', substr($hex, 0, 8), substr($hex, 8, 4), substr($hex, 12, 4), substr($hex, 16, 4), substr($hex, 20));
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

    private function clearEntityManager(): void
    {
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        $entityManager->clear();
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
