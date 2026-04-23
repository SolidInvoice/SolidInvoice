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
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\ClientBundle\Mcp\ClientWriteTools;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Mcp\InvoiceWriteTools;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use SolidInvoice\McpBundle\Mcp\Tool\ResourceWriteTools;
use SolidInvoice\McpBundle\Security\McpOAuthAuthenticator;
use SolidInvoice\McpBundle\Security\McpScope;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Zenstruck\Foundry\Test\Factories;

/**
 * Phase 3 write tool coverage — asserts scope enforcement, company binding,
 * and cross-tenant rejection for the write tools.
 *
 * @group functional
 */
final class WriteToolFlowTest extends KernelTestCase
{
    use EnsureApplicationInstalled;
    use Factories;

    public function testReadOnlyTokenRejectsWrite(): void
    {
        $this->setActiveScopes([McpScope::Read->value]);

        $tool = self::getContainer()->get(ResourceWriteTools::class);
        self::assertInstanceOf(ResourceWriteTools::class, $tool);

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('mcp:write');

        $tool->createResource('client', ['name' => 'Rejected', 'currency_code' => 'USD']);
    }

    public function testCreateClientBindsToActiveCompany(): void
    {
        $this->setActiveScopes([McpScope::Write->value]);

        $tool = self::getContainer()->get(ResourceWriteTools::class);
        self::assertInstanceOf(ResourceWriteTools::class, $tool);

        $result = $tool->createResource('client', [
            'name' => 'Acme Corp',
            'currency_code' => 'USD',
        ]);

        self::assertSame('Acme Corp', $result['name']);
        self::assertSame('USD', $result['currency']);
        self::assertArrayHasKey('id', $result);

        // Round-trip via direct Doctrine read to verify company binding.
        $client = self::getContainer()->get('doctrine')->getRepository(Client::class)->find(\Symfony\Component\Uid\Ulid::fromString($result['id']));
        self::assertInstanceOf(Client::class, $client);
        self::assertSame($this->company->getId()->toRfc4122(), $client->getCompany()->getId()->toRfc4122());
    }

    public function testCreateClientIgnoresCompanyIdFromInput(): void
    {
        $this->setActiveScopes([McpScope::Write->value]);

        $tool = self::getContainer()->get(ResourceWriteTools::class);
        self::assertInstanceOf(ResourceWriteTools::class, $tool);

        // Deliberately try to override company. Must be ignored.
        $forgedCompanyId = (string) new \Symfony\Component\Uid\Ulid();

        $result = $tool->createResource('client', [
            'name' => 'Spoofy',
            'currency_code' => 'EUR',
            'company' => $forgedCompanyId,
            'company_id' => $forgedCompanyId,
        ]);

        $client = self::getContainer()->get('doctrine')->getRepository(Client::class)->find(\Symfony\Component\Uid\Ulid::fromString($result['id']));
        self::assertInstanceOf(Client::class, $client);
        self::assertSame(
            $this->company->getId()->toRfc4122(),
            $client->getCompany()->getId()->toRfc4122(),
            'Client must be bound to the active company, not the one supplied in input.',
        );
    }

    public function testCreateResourceRejectsUnknownResource(): void
    {
        $this->setActiveScopes([McpScope::Write->value]);

        $tool = self::getContainer()->get(ResourceWriteTools::class);
        self::assertInstanceOf(ResourceWriteTools::class, $tool);

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('invoice');

        // Invoice is not in the CREATABLE allowlist (too complex; dedicated tool required).
        $tool->createResource('invoice', []);
    }

    public function testUpdateResourceChangesScalarFields(): void
    {
        $this->setActiveScopes([McpScope::Write->value]);

        $client = ClientFactory::createOne(['company' => $this->company, 'name' => 'Before']);

        $tool = self::getContainer()->get(ResourceWriteTools::class);
        self::assertInstanceOf(ResourceWriteTools::class, $tool);

        $result = $tool->updateResource('client', $client->getId()->toRfc4122(), ['name' => 'After']);

        self::assertSame('After', $result['name']);
    }

    public function testAddContactAttachesToClient(): void
    {
        $this->setActiveScopes([McpScope::Write->value]);

        $client = ClientFactory::createOne(['company' => $this->company]);

        $tool = self::getContainer()->get(ClientWriteTools::class);
        self::assertInstanceOf(ClientWriteTools::class, $tool);

        $result = $tool->addContact(
            $client->getId()->toRfc4122(),
            'new@example.com',
            'Jane',
            'Doe',
        );

        self::assertSame('new@example.com', $result['email']);
        self::assertSame('Jane', $result['first_name']);
        self::assertSame('Doe', $result['last_name']);

        $contact = self::getContainer()->get('doctrine')->getRepository(Contact::class)->find(\Symfony\Component\Uid\Ulid::fromString($result['id']));
        self::assertInstanceOf(Contact::class, $contact);
        self::assertSame($client->getId()->toRfc4122(), $contact->getClient()?->getId()?->toRfc4122());
    }

    public function testAddContactRejectsInvalidEmail(): void
    {
        $this->setActiveScopes([McpScope::Write->value]);

        $client = ClientFactory::createOne(['company' => $this->company]);

        $tool = self::getContainer()->get(ClientWriteTools::class);
        self::assertInstanceOf(ClientWriteTools::class, $tool);

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Invalid email');

        $tool->addContact($client->getId()->toRfc4122(), 'not-an-email');
    }

    public function testInvoiceTransitionRejectsInvalidTransition(): void
    {
        $this->setActiveScopes([McpScope::Write->value]);

        $client = ClientFactory::createOne(['company' => $this->company]);
        $invoice = InvoiceFactory::createOne([
            'company' => $this->company,
            'client' => $client,
            'status' => InvoiceStatus::Paid,
        ]);

        $tool = self::getContainer()->get(InvoiceWriteTools::class);
        self::assertInstanceOf(InvoiceWriteTools::class, $tool);

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('not enabled');

        // "pay" on an already-paid invoice should be rejected.
        $tool->applyInvoiceTransition($invoice->getId()->toRfc4122(), 'pay');
    }

    public function testDeleteRemovesTheRecord(): void
    {
        $this->setActiveScopes([McpScope::Write->value]);

        $client = ClientFactory::createOne(['company' => $this->company]);
        $clientId = $client->getId()->toRfc4122();

        $tool = self::getContainer()->get(ResourceWriteTools::class);
        self::assertInstanceOf(ResourceWriteTools::class, $tool);

        $result = $tool->deleteResource('client', $clientId);

        self::assertTrue($result['deleted']);
        self::assertSame($clientId, $result['id']);

        $reloaded = self::getContainer()->get('doctrine')->getRepository(Client::class)->find(\Symfony\Component\Uid\Ulid::fromString($clientId));
        self::assertNull($reloaded);
    }

    /**
     * @param list<string> $scopes
     */
    private function setActiveScopes(array $scopes): void
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
