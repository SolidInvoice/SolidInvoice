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

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use SolidInvoice\InvoiceBundle\Test\Factory\RecurringInvoiceFactory;
use SolidInvoice\McpBundle\Mcp\Tool\ResourceQueryTools;
use SolidInvoice\McpBundle\Security\McpOAuthAuthenticator;
use SolidInvoice\McpBundle\Security\McpScope;
use SolidInvoice\QuoteBundle\Test\Factory\QuoteFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Reproduces SOL-394: a client row with `currency_code IS NULL` resolves the system
 * default currency on every rendered surface, via {@see \SolidInvoice\ClientBundle\Listener\ClientListener::postLoad()}
 * hydrating `Client::$currency`. The MCP read surface must report that same default
 * rather than `null`, so these fixtures are never persisted-and-reused directly: each
 * test clears the entity manager and re-fetches through {@see ResourceQueryTools},
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

        $result = $this->getResourceTool()->getResource('invoice', $invoiceId->toRfc4122());

        self::assertSame('USD', $result['currency']);
        self::assertSame('USD', $result['client']['currency']);
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

        $result = $this->getResourceTool()->getResource('quote', $quoteId->toRfc4122());

        self::assertSame('USD', $result['currency']);
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

        $result = $this->getResourceTool()->getResource('recurring_invoice', $recurringInvoiceId->toRfc4122());

        self::assertSame('USD', $result['currency']);
    }

    public function testClientWithNullCurrencyReportsSystemDefault(): void
    {
        $this->activateScopes([McpScope::Read->value]);

        $clientId = ClientFactory::createOne(['currencyCode' => null, 'company' => $this->company])->getId();

        $this->clearEntityManager();

        $result = $this->getResourceTool()->getResource('client', $clientId->toRfc4122());

        self::assertSame('USD', $result['currency']);
    }

    /**
     * Guards against the fix reintroducing the SOL-298 defect on this surface: a frozen
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

        $result = $this->getResourceTool()->getResource('invoice', $invoiceId->toRfc4122());

        self::assertSame('USD', $result['currency']);
    }

    private function getResourceTool(): ResourceQueryTools
    {
        $tool = self::getContainer()->get(ResourceQueryTools::class);
        self::assertInstanceOf(ResourceQueryTools::class, $tool);

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
