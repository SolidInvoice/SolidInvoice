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
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\InvoiceBundle\Mcp\RecurringInvoiceReadTools;
use SolidInvoice\InvoiceBundle\Mcp\RecurringInvoiceWriteTools;
use SolidInvoice\McpBundle\Security\McpOAuthAuthenticator;
use SolidInvoice\McpBundle\Security\McpScope;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Uid\Ulid;
use Zenstruck\Foundry\Test\Factories;

/**
 * @group functional
 */
final class RecurringInvoiceCreateTest extends KernelTestCase
{
    use EnsureApplicationInstalled;
    use Factories;

    public function testCreateMonthlyRecurringInvoice(): void
    {
        $this->activateScopes([McpScope::Write->value]);

        $client = ClientFactory::createOne([
            'company' => $this->company,
            'currencyCode' => 'USD',
        ])->_real();

        $tool = self::getContainer()->get(RecurringInvoiceWriteTools::class);
        self::assertInstanceOf(RecurringInvoiceWriteTools::class, $tool);

        $result = $tool->createRecurringInvoice(
            client_id: $client->getId()->toRfc4122(),
            lines: [['description' => 'Monthly retainer', 'price' => 250000, 'qty' => 1]],
            date_start: '2026-05-01',
            schedule: [
                'type' => 'monthly',
                'end_type' => 'after',
                'end_occurrence' => 12,
            ],
            terms: 'Monthly billing',
        );

        self::assertArrayHasKey('id', $result);
        self::assertSame('250000', $result['total']);
        self::assertSame('monthly', $result['schedule']['type']);
        self::assertSame('after', $result['schedule']['end_type']);
        self::assertSame(12, $result['schedule']['end_occurrence']);
        self::assertSame('2026-05-01', substr($result['date_start'], 0, 10));

        $recurring = self::getContainer()->get('doctrine')->getRepository(RecurringInvoice::class)->find(Ulid::fromString($result['id']));
        self::assertInstanceOf(RecurringInvoice::class, $recurring);
        self::assertSame($this->company->getId()->toRfc4122(), $recurring->getCompany()->getId()->toRfc4122());
    }

    public function testCreateRecurringWithEndTypeNever(): void
    {
        $this->activateScopes([McpScope::Write->value]);

        $client = ClientFactory::createOne(['company' => $this->company])->_real();

        $tool = self::getContainer()->get(RecurringInvoiceWriteTools::class);
        self::assertInstanceOf(RecurringInvoiceWriteTools::class, $tool);

        $result = $tool->createRecurringInvoice(
            client_id: $client->getId()->toRfc4122(),
            lines: [['description' => 'Weekly cleanup', 'price' => 5000, 'qty' => 1]],
            date_start: '2026-04-30',
            schedule: ['type' => 'weekly', 'end_type' => 'never'],
        );

        self::assertSame('weekly', $result['schedule']['type']);
        self::assertSame('never', $result['schedule']['end_type']);
        self::assertNull($result['schedule']['end_date']);
        self::assertNull($result['schedule']['end_occurrence']);
    }

    public function testCreateRecurringRejectsInvalidScheduleType(): void
    {
        $this->activateScopes([McpScope::Write->value]);

        $client = ClientFactory::createOne(['company' => $this->company])->_real();

        $tool = self::getContainer()->get(RecurringInvoiceWriteTools::class);
        self::assertInstanceOf(RecurringInvoiceWriteTools::class, $tool);

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Invalid schedule.type');

        $tool->createRecurringInvoice(
            client_id: $client->getId()->toRfc4122(),
            lines: [['description' => 'Bad', 'price' => 1, 'qty' => 1]],
            date_start: '2026-05-01',
            schedule: ['type' => 'hourly'],
        );
    }

    public function testCreateRecurringRequiresEndDateWhenEndTypeIsOn(): void
    {
        $this->activateScopes([McpScope::Write->value]);

        $client = ClientFactory::createOne(['company' => $this->company])->_real();

        $tool = self::getContainer()->get(RecurringInvoiceWriteTools::class);
        self::assertInstanceOf(RecurringInvoiceWriteTools::class, $tool);

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('end_date');

        $tool->createRecurringInvoice(
            client_id: $client->getId()->toRfc4122(),
            lines: [['description' => 'Lines', 'price' => 100, 'qty' => 1]],
            date_start: '2026-05-01',
            schedule: ['type' => 'monthly', 'end_type' => 'on'],
        );
    }

    public function testListRecurringInvoicesReturnsEmptyByDefault(): void
    {
        $this->activateScopes([McpScope::Read->value]);

        $tool = self::getContainer()->get(RecurringInvoiceReadTools::class);
        self::assertInstanceOf(RecurringInvoiceReadTools::class, $tool);

        $result = $tool->listRecurringInvoices();

        self::assertSame(['results' => [], 'count' => 0], $result);
    }

    public function testCreateRecurringReadOnlyRejected(): void
    {
        $this->activateScopes([McpScope::Read->value]);

        $client = ClientFactory::createOne(['company' => $this->company])->_real();

        $tool = self::getContainer()->get(RecurringInvoiceWriteTools::class);
        self::assertInstanceOf(RecurringInvoiceWriteTools::class, $tool);

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('mcp:write');

        $tool->createRecurringInvoice(
            client_id: $client->getId()->toRfc4122(),
            lines: [['description' => 'x', 'price' => 1, 'qty' => 1]],
            date_start: '2026-05-01',
            schedule: ['type' => 'monthly'],
        );
    }

    /**
     * @param list<string> $scopes
     */
    private function activateScopes(array $scopes): void
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
