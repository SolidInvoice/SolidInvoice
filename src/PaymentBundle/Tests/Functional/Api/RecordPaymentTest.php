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

namespace SolidInvoice\PaymentBundle\Tests\Functional\Api;

use SolidInvoice\ApiBundle\Test\ApiTestCase;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use SolidInvoice\PaymentBundle\Entity\Payment;
use SolidInvoice\PaymentBundle\Test\Factory\PaymentMethodFactory;
use Symfony\Component\HttpFoundation\Response;
use Zenstruck\Foundry\Test\Factories;

/**
 * @group functional
 */
final class RecordPaymentTest extends ApiTestCase
{
    use Factories;

    protected function getResourceClass(): string
    {
        return Payment::class;
    }

    public function testRecordPayment(): void
    {
        $client = ClientFactory::createOne(['currencyCode' => 'USD'])->_real();
        $invoice = InvoiceFactory::createOne(['status' => InvoiceStatus::Pending, 'client' => $client])->_real();

        PaymentMethodFactory::createOne([
            'factoryName' => 'offline',
            'enabled' => true,
            'internal' => false,
        ]);

        $response = self::$client->request('POST', $this->getIriFromResource($invoice) . '/payments', [
            'json' => ['amount' => 1000, 'currency' => 'USD'],
            'headers' => ['content-type' => 'application/ld+json', 'accept' => 'application/ld+json'],
        ]);

        static::assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $result = $response->toArray(false);

        self::assertArrayHasKey('id', $result);
        self::assertSame('captured', $result['status']);
        self::assertSame(1000, $result['totalAmount']);
        self::assertSame('USD', $result['currencyCode']);
    }

    public function testCannotRecordPaymentForDraftInvoice(): void
    {
        $client = ClientFactory::createOne(['currencyCode' => 'USD'])->_real();
        $invoice = InvoiceFactory::createOne(['status' => InvoiceStatus::Draft, 'client' => $client])->_real();

        PaymentMethodFactory::createOne([
            'factoryName' => 'offline',
            'enabled' => true,
            'internal' => false,
        ]);

        self::$client->request(
            'POST',
            $this->getIriFromResource($invoice) . '/payments',
            [
                'json' => ['amount' => 1000, 'currency' => 'USD'],
                'headers' => [
                    'content-type' => 'application/ld+json',
                    'accept' => 'application/ld+json',
                ],
            ]
        );

        static::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testRecordPaymentForForeignCompanyInvoice(): void
    {
        $otherCompany = CompanyFactory::new()->create();
        self::getContainer()->get(CompanySelector::class)->switchCompany($otherCompany->getId());
        $foreignClient = ClientFactory::createOne(['company' => $otherCompany])->_real();
        $foreignInvoice = InvoiceFactory::createOne(['client' => $foreignClient, 'status' => InvoiceStatus::Pending])->_real();
        self::getContainer()->get(CompanySelector::class)->switchCompany($this->company->getId());

        PaymentMethodFactory::createOne([
            'factoryName' => 'offline',
            'enabled' => true,
            'internal' => false,
        ]);

        self::$client->request(
            'POST',
            $this->getIriFromResource($foreignInvoice) . '/payments',
            [
                'json' => ['amount' => 1000, 'currency' => 'USD'],
                'headers' => [
                    'content-type' => 'application/ld+json',
                    'accept' => 'application/ld+json',
                ],
            ]
        );

        static::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}
