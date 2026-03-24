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
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use SolidInvoice\PaymentBundle\Entity\Payment;
use SolidInvoice\PaymentBundle\Enum\PaymentStatus;
use SolidInvoice\PaymentBundle\Test\Factory\PaymentFactory;
use Zenstruck\Foundry\Test\Factories;

/**
 * @group functional
 */
final class PaymentTest extends ApiTestCase
{
    use Factories;

    protected function getResourceClass(): string
    {
        return Payment::class;
    }

    public function testGetPaymentsForInvoice(): void
    {
        $invoice = InvoiceFactory::createOne()->_real();
        $payment = PaymentFactory::createOne([
            'invoice' => $invoice,
            'status' => PaymentStatus::Captured,
        ])->_real();

        $data = $this->requestGet($this->getIriFromResource($invoice) . '/payments');
        unset($data['search'], $data['view']);

        self::assertEqualsCanonicalizing([
            '@context' => $this->getContextForResource($payment),
            '@id' => $this->getIriFromResource($invoice) . '/payments',
            '@type' => 'Collection',
            'totalItems' => 1,
            'member' => [
                [
                    '@id' => $this->getIriFromResource($payment),
                    '@type' => 'Payment',
                    'id' => $payment->getId()->toString(),
                    'companyId' => $this->company->getId()->toBase58(),
                    'invoice' => $this->getIriFromResource($invoice),
                    'client' => null,
                    'method' => null,
                    'status' => 'captured',
                    'message' => $payment->getMessage(),
                    'completed' => $payment->getCompleted()->format('c'),
                    'reference' => null,
                    'notes' => null,
                    'number' => $payment->getNumber(),
                    'description' => $payment->getDescription(),
                    'clientEmail' => $payment->getClientEmail(),
                    'clientId' => null,
                    'totalAmount' => $payment->getTotalAmount(),
                    'currencyCode' => $payment->getCurrencyCode(),
                    'details' => [],
                    'creditCard' => null,
                    'bankAccount' => null,
                    'amount' => [
                        'amount' => $payment->getAmount()->getAmount(),
                        'currency' => $payment->getAmount()->getCurrency()->getCode(),
                    ],
                    'total' => $payment->getAmount()->getAmount() / 100,
                ],
            ],
        ], $data);
    }

    public function testGetPaymentsForClient(): void
    {
        $client = ClientFactory::createOne()->_real();
        $payment = PaymentFactory::createOne([
            'client' => $client,
            'status' => PaymentStatus::Captured,
        ])->_real();

        // Create multiple additional payments to ensure we only receive the payments for the specified client
        PaymentFactory::createMany(5, ['client' => ClientFactory::new()]);

        $data = $this->requestGet($this->getIriFromResource($client) . '/payments');
        unset($data['search'], $data['view']);

        self::assertEqualsCanonicalizing([
            '@context' => $this->getContextForResource($payment),
            '@id' => $this->getIriFromResource($client) . '/payments',
            '@type' => 'Collection',
            'totalItems' => 1,
            'member' => [
                [
                    '@id' => $this->getIriFromResource($payment),
                    '@type' => 'Payment',
                    'id' => $payment->getId()->toString(),
                    'companyId' => $this->company->getId()->toBase58(),
                    'invoice' => null,
                    'client' => $this->getIriFromResource($client),
                    'method' => null,
                    'status' => 'captured',
                    'message' => $payment->getMessage(),
                    'completed' => $payment->getCompleted()->format('c'),
                    'reference' => null,
                    'notes' => null,
                    'number' => $payment->getNumber(),
                    'description' => $payment->getDescription(),
                    'clientEmail' => $payment->getClientEmail(),
                    'clientId' => $client->getId()->toString(),
                    'totalAmount' => $payment->getTotalAmount(),
                    'currencyCode' => $payment->getCurrencyCode(),
                    'details' => [],
                    'creditCard' => null,
                    'bankAccount' => null,
                    'amount' => [
                        'amount' => $payment->getAmount()->getAmount(),
                        'currency' => $payment->getAmount()->getCurrency()->getCode(),
                    ],
                    'total' => $payment->getAmount()->getAmount() / 100,
                ],
            ],
        ], $data);
    }

    /**
     * Ensure we can't receive any payments for an archived client
     */
    public function testGetPaymentsForArchivedClient(): void
    {
        $client = ClientFactory::createOne(['archived' => true])->_real();

        PaymentFactory::createOne(['client' => $client]);

        $data = $this->requestGet($this->getIriFromResource($client) . '/payments');
        unset($data['search'], $data['view']);

        self::assertEqualsCanonicalizing([
            '@context' => $this->getContextForResource(Payment::class),
            '@id' => $this->getIriFromResource($client) . '/payments',
            '@type' => 'Collection',
            'totalItems' => 0,
            'member' => [],
        ], $data);
    }

    /**
     * Ensure we can't receive any payments for a different company
     */
    public function testGetPaymentsForDifferentCompany(): void
    {
        $company = CompanyFactory::new()->create();
        self::getContainer()->get(CompanySelector::class)->switchCompany($company->getId());
        $client = ClientFactory::createOne(['company' => $company])->_real();
        self::getContainer()->get(CompanySelector::class)->switchCompany($this->company->getId());

        PaymentFactory::createOne(['client' => $client]);

        $data = $this->requestGet($this->getIriFromResource($client) . '/payments');
        unset($data['search'], $data['view']);

        self::assertEqualsCanonicalizing([
            '@context' => $this->getContextForResource(Payment::class),
            '@id' => $this->getIriFromResource($client) . '/payments',
            '@type' => 'Collection',
            'totalItems' => 0,
            'member' => [],
        ], $data);
    }

    public function testGet(): void
    {
        $client = ClientFactory::createOne()->_real();
        $invoice = InvoiceFactory::createOne(['client' => $client])->_real();
        $payment = PaymentFactory::createOne([
            'client' => $client,
            'invoice' => $invoice,
            'status' => PaymentStatus::Captured,
        ])->_real();

        $data = $this->requestGet($this->getIriFromResource($payment));

        self::assertEqualsCanonicalizing([
            '@context' => $this->getContextForResource($payment),
            '@id' => $this->getIriFromResource($payment),
            '@type' => 'Payment',
            'id' => $payment->getId()->toString(),
            'companyId' => $this->company->getId()->toBase58(),
            'invoice' => $this->getIriFromResource($invoice),
            'client' => $this->getIriFromResource($client),
            'method' => null,
            'status' => 'captured',
            'message' => $payment->getMessage(),
            'completed' => $payment->getCompleted()->format('c'),
            'reference' => null,
            'notes' => null,
            'number' => $payment->getNumber(),
            'description' => $payment->getDescription(),
            'clientEmail' => $payment->getClientEmail(),
            'clientId' => $client->getId()->toString(),
            'totalAmount' => $payment->getTotalAmount(),
            'currencyCode' => $payment->getCurrencyCode(),
            'details' => [],
            'creditCard' => null,
            'bankAccount' => null,
            'amount' => [
                'amount' => $payment->getAmount()->getAmount(),
                'currency' => $payment->getAmount()->getCurrency()->getCode(),
            ],
            'total' => $payment->getAmount()->getAmount() / 100,
        ], $data);
    }

    public function testGetAll(): void
    {
        ClientFactory::createMany(4);

        PaymentFactory::createMany(4, [
            'client' => ClientFactory::random(),
            'invoice' => InvoiceFactory::new(),
        ]);

        $data = $this->requestGet('/api/payments');

        self::assertArraySubset([
            '@context' => $this->getContextForResource(Payment::class),
            '@id' => '/api/payments',
            '@type' => 'Collection',
        ], $data);
    }
}
