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
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Mcp\Exception\ToolCallException;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Enum\ClientStatus;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Mcp\InvoiceWriteTools;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use SolidInvoice\McpBundle\Mcp\Tool\ResourceQueryTools;
use SolidInvoice\McpBundle\Mcp\Tool\ResourceWriteTools;
use SolidInvoice\McpBundle\Security\McpOAuthAuthenticator;
use SolidInvoice\McpBundle\Security\McpScope;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Zenstruck\Foundry\Test\Factories;

/**
 * Verifies that a token bound to company A cannot read or write records
 * belonging to company B. The CompanyFilter and write-path company overrides
 * make this invariant hold.
 *
 * @group functional
 */
final class CrossTenantTest extends KernelTestCase
{
    use EnsureApplicationInstalled;
    use Factories;

    /**
     * @var array{company: Company, client: Client, invoice: Invoice}|null
     */
    private ?array $foreign = null;

    public function testListResourceHidesOtherCompanyInvoices(): void
    {
        $this->seedForeignTenant();

        // Invoice in the active company (created after the filter is reset).
        InvoiceFactory::createOne([
            'company' => $this->company,
            'client' => ClientFactory::createOne(['company' => $this->company, 'name' => 'Our Client']),
            'status' => InvoiceStatus::Pending,
        ]);

        $this->activateToken(McpScope::Read);

        $tool = self::getContainer()->get(ResourceQueryTools::class);
        self::assertInstanceOf(ResourceQueryTools::class, $tool);

        $result = $tool->listResource('invoice');

        self::assertSame(1, $result['total'], 'CompanyFilter should hide foreign-company invoices.');
        self::assertCount(1, $result['results']);
        self::assertSame('Our Client', $result['results'][0]['client']['name']);
    }

    public function testGetResourceReturnsNotFoundForOtherCompanyRecord(): void
    {
        $this->seedForeignTenant();
        self::assertNotNull($this->foreign);
        $foreignId = $this->foreign['invoice']->getId()?->toRfc4122();
        self::assertNotNull($foreignId);

        $this->activateToken(McpScope::Read);

        $tool = self::getContainer()->get(ResourceQueryTools::class);
        self::assertInstanceOf(ResourceQueryTools::class, $tool);

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('not found');

        $tool->getResource('invoice', $foreignId);
    }

    public function testInvoiceTransitionOnForeignInvoiceFails(): void
    {
        $this->seedForeignTenant();
        self::assertNotNull($this->foreign);
        $foreignId = $this->foreign['invoice']->getId()?->toRfc4122();
        self::assertNotNull($foreignId);

        $this->activateToken(McpScope::Write);

        $tool = self::getContainer()->get(InvoiceWriteTools::class);
        self::assertInstanceOf(InvoiceWriteTools::class, $tool);

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('not found');

        $tool->applyInvoiceTransition($foreignId, 'pay');
    }

    public function testUpdateOnForeignClientFails(): void
    {
        $this->seedForeignTenant();
        self::assertNotNull($this->foreign);
        $foreignId = $this->foreign['client']->getId()?->toRfc4122();
        self::assertNotNull($foreignId);

        $this->activateToken(McpScope::Write);

        $tool = self::getContainer()->get(ResourceWriteTools::class);
        self::assertInstanceOf(ResourceWriteTools::class, $tool);

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('not found');

        $tool->updateResource('client', $foreignId, ['name' => 'Hijacked']);
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
        $invoice->setInvoiceDate(new DateTimeImmutable());
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

        while ($stack->getMainRequest() !== null) {
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
