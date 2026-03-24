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

namespace SolidInvoice\InvoiceBundle\Tests\Functional\Api;

use SolidInvoice\ApiBundle\Test\ApiTestCase;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\ClientBundle\Test\Factory\ContactFactory;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\InvoiceBundle\Enum\RecurringInvoiceStatus;
use SolidInvoice\InvoiceBundle\Test\Factory\RecurringInvoiceFactory;
use Symfony\Component\HttpFoundation\Response;
use Zenstruck\Foundry\Test\Factories;

/**
 * @group functional
 */
final class RecurringInvoiceTransitionTest extends ApiTestCase
{
    use Factories;

    protected function getResourceClass(): string
    {
        return RecurringInvoice::class;
    }

    public function testActivateRecurringInvoice(): void
    {
        $client = ClientFactory::createOne()->_real();
        $contacts = ContactFactory::createMany(1, ['client' => $client]);
        $recurringInvoice = RecurringInvoiceFactory::createOne([
            'status' => RecurringInvoiceStatus::Draft,
            'users' => $contacts,
        ])->_real();

        $result = $this->requestPost(
            sprintf('/api/recurring-invoices/%s/transitions/activate', $recurringInvoice->getId()),
            []
        );

        self::assertSame('active', $result['status']);
    }

    public function testCancelRecurringInvoice(): void
    {
        $client = ClientFactory::createOne()->_real();
        $contacts = ContactFactory::createMany(1, ['client' => $client]);
        $recurringInvoice = RecurringInvoiceFactory::createOne([
            'status' => RecurringInvoiceStatus::Draft,
            'users' => $contacts,
        ])->_real();

        $result = $this->requestPost(
            sprintf('/api/recurring-invoices/%s/transitions/cancel', $recurringInvoice->getId()),
            []
        );

        self::assertSame('cancelled', $result['status']);
    }

    public function testInvalidTransition(): void
    {
        $recurringInvoice = RecurringInvoiceFactory::createOne(['status' => RecurringInvoiceStatus::Draft])->_real();

        self::$client->request(
            'POST',
            sprintf('/api/recurring-invoices/%s/transitions/complete', $recurringInvoice->getId()),
            [
                'headers' => [
                    'content-type' => 'application/ld+json',
                    'accept' => 'application/ld+json',
                ],
                'json' => [],
            ]
        );

        static::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testGenerateInvoice(): void
    {
        $client = ClientFactory::createOne()->_real();
        $contacts = ContactFactory::createMany(1, ['client' => $client]);
        $recurringInvoice = RecurringInvoiceFactory::createOne([
            'status' => RecurringInvoiceStatus::Active,
            'users' => $contacts,
        ])->_real();

        $result = $this->requestPostExpecting(
            sprintf('/api/recurring-invoices/%s/generate', $recurringInvoice->getId()),
            [],
            Invoice::class
        );

        self::assertSame('Invoice', $result['@type']);
        self::assertArrayHasKey('id', $result);
    }

    public function testTransitionOnForeignCompanyRecurringInvoice(): void
    {
        $otherCompany = CompanyFactory::new()->create();
        self::getContainer()->get(CompanySelector::class)->switchCompany($otherCompany->getId());
        $foreignClient = ClientFactory::createOne(['company' => $otherCompany]);
        $foreignRecurringInvoice = RecurringInvoiceFactory::createOne([
            'client' => $foreignClient,
            'status' => RecurringInvoiceStatus::Draft,
        ])->_real();
        self::getContainer()->get(CompanySelector::class)->switchCompany($this->company->getId());

        self::$client->request(
            'POST',
            sprintf('/api/recurring-invoices/%s/transitions/activate', $foreignRecurringInvoice->getId()),
            [
                'headers' => [
                    'content-type' => 'application/ld+json',
                    'accept' => 'application/ld+json',
                ],
                'json' => [],
            ]
        );

        static::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testGenerateForeignCompanyRecurringInvoice(): void
    {
        $otherCompany = CompanyFactory::new()->create();
        self::getContainer()->get(CompanySelector::class)->switchCompany($otherCompany->getId());
        $foreignClient = ClientFactory::createOne(['company' => $otherCompany]);
        $foreignRecurringInvoice = RecurringInvoiceFactory::createOne([
            'client' => $foreignClient,
            'status' => RecurringInvoiceStatus::Active,
        ])->_real();
        self::getContainer()->get(CompanySelector::class)->switchCompany($this->company->getId());

        self::$client->request(
            'POST',
            sprintf('/api/recurring-invoices/%s/generate', $foreignRecurringInvoice->getId()),
            [
                'headers' => [
                    'content-type' => 'application/ld+json',
                    'accept' => 'application/ld+json',
                ],
                'json' => [],
            ]
        );

        static::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}
