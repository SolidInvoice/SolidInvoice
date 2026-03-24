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
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use Symfony\Component\HttpFoundation\Response;
use Zenstruck\Foundry\Test\Factories;

/**
 * @group functional
 */
final class InvoiceTransitionTest extends ApiTestCase
{
    use Factories;

    protected function getResourceClass(): string
    {
        return Invoice::class;
    }

    public function testAcceptInvoice(): void
    {
        $client = ClientFactory::createOne()->_real();
        $contacts = ContactFactory::createMany(1, ['client' => $client]);
        $invoice = InvoiceFactory::createOne([
            'status' => InvoiceStatus::Draft,
            'users' => $contacts,
        ])->_real();

        $result = $this->requestPost(
            sprintf('/api/invoices/%s/transitions/accept', $invoice->getId()),
            []
        );

        self::assertSame('pending', $result['status']);
    }

    public function testCancelInvoice(): void
    {
        $client = ClientFactory::createOne()->_real();
        $contacts = ContactFactory::createMany(1, ['client' => $client]);
        $invoice = InvoiceFactory::createOne([
            'status' => InvoiceStatus::Draft,
            'users' => $contacts,
        ])->_real();

        $result = $this->requestPost(
            sprintf('/api/invoices/%s/transitions/cancel', $invoice->getId()),
            []
        );

        self::assertSame('cancelled', $result['status']);
    }

    public function testInvalidTransition(): void
    {
        $invoice = InvoiceFactory::createOne(['status' => InvoiceStatus::Draft])->_real();

        self::$client->request(
            'POST',
            sprintf('/api/invoices/%s/transitions/pay', $invoice->getId()),
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

    public function testTransitionOnForeignCompanyInvoice(): void
    {
        $otherCompany = CompanyFactory::new()->create();
        self::getContainer()->get(CompanySelector::class)->switchCompany($otherCompany->getId());
        $foreignClient = ClientFactory::createOne(['company' => $otherCompany]);
        $foreignInvoice = InvoiceFactory::createOne([
            'client' => $foreignClient,
            'status' => InvoiceStatus::Draft,
        ])->_real();
        self::getContainer()->get(CompanySelector::class)->switchCompany($this->company->getId());

        self::$client->request(
            'POST',
            sprintf('/api/invoices/%s/transitions/accept', $foreignInvoice->getId()),
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
