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

use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\ApiBundle\Test\ApiTestCase;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use SolidInvoice\PaymentBundle\Entity\Payment;
use SolidInvoice\PaymentBundle\Enum\PaymentStatus;
use SolidInvoice\PaymentBundle\Test\Factory\PaymentFactory;
use function array_map;

#[Group('functional')]
final class PaymentTest extends ApiTestCase
{
    protected function getResourceClass(): string
    {
        return Payment::class;
    }

    public function testGetPaymentsForInvoiceWithClient(): void
    {
        $client = ClientFactory::createOne();
        $invoice = InvoiceFactory::createOne(['client' => $client]);
        $payment = PaymentFactory::createOne([
            'invoice' => $invoice,
            'client' => $client,
            'status' => PaymentStatus::Captured,
        ]);

        $data = $this->requestGet($this->getIriFromResource($invoice) . '/payments');
        unset($data['search'], $data['view']);

        self::assertSame(1, $data['totalItems']);
        self::assertCount(1, $data['member']);
        self::assertSame($this->getIriFromResource($payment), $data['member'][0]['@id']);
        self::assertSame($this->getIriFromResource($invoice), $data['member'][0]['invoice']);
        self::assertSame($this->getIriFromResource($client), $data['member'][0]['client']);
    }

    public function testGetPaymentsForInvoice(): void
    {
        $invoice = InvoiceFactory::createOne();
        $payment = PaymentFactory::createOne([
            'invoice' => $invoice,
            'status' => PaymentStatus::Captured,
        ]);

        $data = $this->requestGet($this->getIriFromResource($invoice) . '/payments');
        unset($data['search'], $data['view']);
        $data['member'] = array_map(self::assertTimestampsAndRemove(...), $data['member']);

        self::assertEqualsCanonicalizing([
            '@context' => $this->getContextForResource($payment),
            '@id' => $this->getIriFromResource($invoice) . '/payments',
            '@type' => 'Collection',
            'totalItems' => 1,
            'member' => [
                [
                    '@id' => $this->getIriFromResource($payment),
                    '@type' => 'Payment',
                    'id' => $payment->getId()
                        ->toString(),
                    'invoice' => $this->getIriFromResource($invoice),
                    'client' => null,
                    'method' => null,
                    'status' => 'captured',
                    'reference' => null,
                    'notes' => null,
                    'currencyCode' => $payment->getCurrencyCode(),
                    'amount' => $payment->getTotalAmount() / 100,
                    'paidAt' => CarbonImmutable::instance($payment->getCompleted())->utc()->format('c'),
                ],
            ],
        ], $data);
    }

    public function testGetPaymentsForClient(): void
    {
        $client = ClientFactory::createOne();
        $payment = PaymentFactory::createOne([
            'client' => $client,
            'status' => PaymentStatus::Captured,
        ]);

        // Create multiple additional payments to ensure we only receive the payments for the specified client
        PaymentFactory::createMany(5, ['client' => ClientFactory::new()]);

        $data = $this->requestGet($this->getIriFromResource($client) . '/payments');
        unset($data['search'], $data['view']);
        $data['member'] = array_map(self::assertTimestampsAndRemove(...), $data['member']);

        self::assertEqualsCanonicalizing([
            '@context' => $this->getContextForResource($payment),
            '@id' => $this->getIriFromResource($client) . '/payments',
            '@type' => 'Collection',
            'totalItems' => 1,
            'member' => [
                [
                    '@id' => $this->getIriFromResource($payment),
                    '@type' => 'Payment',
                    'id' => $payment->getId()
                        ->toString(),
                    'invoice' => null,
                    'client' => $this->getIriFromResource($client),
                    'method' => null,
                    'status' => 'captured',
                    'reference' => null,
                    'notes' => null,
                    'currencyCode' => $payment->getCurrencyCode(),
                    'amount' => $payment->getTotalAmount() / 100,
                    'paidAt' => CarbonImmutable::instance($payment->getCompleted())->utc()->format('c'),
                ],
            ],
        ], $data);
    }

    /**
     * Ensure we can't receive any payments for an archived client
     */
    public function testGetPaymentsForArchivedClient(): void
    {
        $client = ClientFactory::createOne(['archived' => true]);

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
        $client = ClientFactory::createOne(['company' => $company]);
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
        $client = ClientFactory::createOne();
        $invoice = InvoiceFactory::createOne(['client' => $client]);
        $payment = PaymentFactory::createOne([
            'client' => $client,
            'invoice' => $invoice,
            'status' => PaymentStatus::Captured,
        ]);
        $data = self::assertTimestampsAndRemove($this->requestGet($this->getIriFromResource($payment)));

        self::assertEqualsCanonicalizing([
            '@context' => $this->getContextForResource($payment),
            '@id' => $this->getIriFromResource($payment),
            '@type' => 'Payment',
            'id' => $payment->getId()
                ->toString(),
            'invoice' => $this->getIriFromResource($invoice),
            'client' => $this->getIriFromResource($client),
            'method' => null,
            'status' => 'captured',
            'reference' => null,
            'notes' => null,
            'currencyCode' => $payment->getCurrencyCode(),
            'amount' => $payment->getTotalAmount() / 100,
            'paidAt' => CarbonImmutable::instance($payment->getCompleted())->utc()->format('c'),
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
