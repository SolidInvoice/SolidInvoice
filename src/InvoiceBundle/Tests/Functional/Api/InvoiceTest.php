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

use DateTime;
use SolidInvoice\ApiBundle\Test\ApiTestCase;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\ClientBundle\Test\Factory\ContactFactory;
use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\Line;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Uid\Uuid;
use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Test\Factories;
use function array_map;
use function date;

/**
 * @group functional
 */
final class InvoiceTest extends ApiTestCase
{
    use Factories;

    protected function getResourceClass(): string
    {
        return Invoice::class;
    }

    public function testCreate(): void
    {
        $client = ClientFactory::createOne()->_real();

        $contacts = array_map(
            fn (Proxy $contact) => $this->getIriFromResource($contact->_real()),
            ContactFactory::createMany($this->faker->numberBetween(1, 5), ['client' => $client])
        );

        $data = [
            'users' => $contacts,
            'client' => $this->getIriFromResource($client),
            'discount' => [
                'type' => 'percentage',
                'value' => 10,
            ],
            'lines' => [
                [
                    'price' => 100,
                    'qty' => 1,
                    'description' => 'Foo Item',
                ],
            ],
        ];

        $result = $this->requestPost('/api/invoices', $data);

        self::assertTrue(Ulid::isValid($result['id']));
        self::assertTrue(Uuid::isValid($result['uuid']));
        self::assertTrue(Ulid::isValid($result['lines'][0]['id']));

        self::assertJsonContains([
            '@context' => $this->getContextForResource($this->getResourceClass()),
            '@type' => 'Invoice',
            'client' => $this->getIriFromResource($client),
            'balance' => 90,
            'due' => null,
            'invoiceDate' => date('Y-m-d\T00:00:00+02:00'),
            'paidDate' => null,
            'lines' => [
                [
                    'description' => 'Foo Item',
                    'price' => 100,
                    'qty' => 1,
                    'tax' => null,
                    'total' => 100,
                ],
            ],
            'users' => $contacts,
            'status' => 'draft',
            'total' => 90,
            'baseTotal' => 100,
            'tax' => 0,
            'discount' => [
                'type' => 'percentage',
                'value' => 10,
            ],
            'terms' => null,
            'notes' => null,
        ]);
    }

    public function testDelete(): void
    {
        $client = ClientFactory::createOne();
        $invoice = InvoiceFactory::createOne(['client' => $client])->_real();

        $this->requestDelete($this->getIriFromResource($invoice));
    }

    public function testGet(): void
    {
        $client = ClientFactory::createOne();
        $contacts = ContactFactory::createMany($this->faker->numberBetween(1, 5), ['client' => $client]);

        /** @var Invoice $invoice */
        $invoice = InvoiceFactory::createOne([
            'client' => $client,
            'users' => $contacts,
            'due' => new DateTime('2005-01-20'),
            'paidDate' => null,
            'discount' => (new Discount())
                ->setType('percentage')
                ->setValue(0),
            'lines' => [
                (new Line())
                    ->setDescription('Test Item')
                    ->setQty(1)
                    ->setPrice(10000),
            ],
        ])->_real();

        $data = $this->requestGet($this->getIriFromResource($invoice));

        self::assertSame([
            '@context' => '/api/contexts/Invoice',
            '@id' => $this->getIriFromResource($invoice),
            '@type' => 'Invoice',
            'id' => $invoice->getId()->toString(),
            'invoiceId' => '',
            'uuid' => $invoice->getUuid()->toString(),
            'client' => '/api/clients/' . $invoice->getClient()->getId(),
            'balance' => 100,
            'due' => '2005-01-20T00:00:00+02:00',
            'invoiceDate' => date('Y-m-d\T00:00:00+02:00'),
            'paidDate' => null,
            'payments' => [],
            'quote' => null,
            'lines' => [
                [
                    '@id' => $this->getIriFromResource($invoice->getLines()->first()),
                    '@type' => 'InvoiceLine',
                    'id' => $invoice->getLines()->first()->getId()->toString(),
                    'description' => 'Test Item',
                    'price' => 100,
                    'qty' => 1,
                    'tax' => null,
                    'total' => 100,
                ],
            ],
            'users' => array_map(fn (Proxy $contact) => $this->getIriFromResource($contact->_real()), $contacts),
            'status' => $invoice->getStatus(),
            'total' => 100,
            'baseTotal' => 100,
            'tax' => 0,
            'discount' => [
                'type' => $invoice->getDiscount()->getType(),
                'value' => 0,
            ],
            'terms' => $invoice->getTerms(),
            'notes' => $invoice->getNotes(),
        ], $data);
    }

    public function testEdit(): void
    {
        $client = ClientFactory::createOne();
        $contacts = ContactFactory::createMany($this->faker->numberBetween(1, 5), ['client' => $client]);

        /** @var Invoice $invoice */
        $invoice = InvoiceFactory::createOne([
            'client' => $client,
            'due' => new DateTime('2005-01-20'),
            'paidDate' => null,
            'users' => $contacts,
            'lines' => [
                (new Line())
                    ->setDescription('Test Item')
                    ->setQty(1)
                    ->setPrice(10000),
                (new Line())
                    ->setDescription('Test Item Too')
                    ->setQty(1)
                    ->setPrice(10000),
            ],
        ])->_real();

        $data = $this->requestPatch(
            $this->getIriFromResource($invoice),
            [
                'discount' => [
                    'type' => 'percentage',
                    'value' => 10,
                ],
                'lines' => [
                    [
                        'price' => 10000,
                        'qty' => 1,
                        'description' => 'Foo Item',
                    ],
                ],
            ]
        );

        self::assertSame([
            '@context' => '/api/contexts/Invoice',
            '@id' => $this->getIriFromResource($invoice),
            '@type' => 'Invoice',
            'id' => $invoice->getId()->toString(),
            'invoiceId' => '',
            'uuid' => $invoice->getUuid()->toString(),
            'client' => $this->getIriFromResource($invoice->getClient()),
            'balance' => 9000,
            'due' => '2005-01-20T00:00:00+02:00',
            'invoiceDate' => date('Y-m-d\T00:00:00+02:00'),
            'paidDate' => null,
            'payments' => [],
            'quote' => null,
            'lines' => [
                [
                    '@id' => $this->getIriFromResource($invoice->getLines()->first()),
                    '@type' => 'InvoiceLine',
                    'id' => $invoice->getLines()->first()->getId()->toString(),
                    'description' => 'Foo Item',
                    'price' => 10000,
                    'qty' => 1,
                    'tax' => null,
                    'total' => 10000,
                ],
            ],
            'users' => array_map(fn (Proxy $contact) => $this->getIriFromResource($contact->_real()), $contacts),
            'status' => $invoice->getStatus(),
            'total' => 9000,
            'baseTotal' => 10000,
            'tax' => 0,
            'discount' => [
                'type' => $invoice->getDiscount()->getType(),
                'value' => 10,
            ],
            'terms' => $invoice->getTerms(),
            'notes' => $invoice->getNotes(),
        ], $data);
    }
}
