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

use DateTimeInterface;
use SolidInvoice\ApiBundle\Test\ApiTestCase;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\ClientBundle\Test\Factory\ContactFactory;
use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoiceLine;
use SolidInvoice\InvoiceBundle\Test\Factory\RecurringInvoiceFactory;
use Symfony\Component\Uid\Ulid;
use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Test\Factories;
use function array_map;

/**
 * @group functional
 */
final class RecurringInvoiceTest extends ApiTestCase
{
    use Factories;

    protected function getResourceClass(): string
    {
        return RecurringInvoice::class;
    }

    public function testCreate(): void
    {
        $client = ClientFactory::createOne()->_real();

        $contacts = array_map(
            fn (Proxy $contact) => $this->getIriFromResource($contact->_real()),
            ContactFactory::createMany($this->faker->numberBetween(1, 5), ['client' => $client])
        );

        $date = date(DateTimeInterface::ATOM);

        $data = [
            'users' => $contacts,
            'client' => $this->getIriFromResource($client),
            'frequency' => '* * * * *',
            'dateStart' => $date,
            'dateEnd' => null,
            'discount' => [
                'type' => 'percentage',
                'value' => 10.0,
            ],
            'lines' => [
                [
                    'price' => 100.10,
                    'qty' => 1.0,
                    'description' => 'Foo Line',
                ],
            ],
        ];

        $result = $this->requestPost('/api/recurring-invoices', $data);

        self::assertArrayHasKey('id', $result);
        self::assertArrayHasKey('id', $result['lines'][0]);
        self::assertTrue(Ulid::isValid($result['id']));
        self::assertTrue(Ulid::isValid($result['lines'][0]['id']));

        unset($result['id'], $result['@id'], $result['lines'][0]['id'], $result['lines'][0]['@id']);

        self::assertEqualsCanonicalizing([
            '@context' => '/api/contexts/RecurringInvoice',
            '@type' => 'RecurringInvoice',
            'client' => $this->getIriFromResource($client),
            'frequency' => '* * * * *',
            'dateStart' => date('Y-m-d\T00:00:00+02:00'),
            'dateEnd' => null,
            'lines' => [
                [
                    '@type' => 'RecurringInvoiceLine',
                    'description' => 'Foo Line',
                    'price' => 100.1,
                    'qty' => 1,
                    'tax' => null,
                    'total' => 100.1,
                ],
            ],
            'users' => $contacts,
            'status' => 'draft',
            'total' => 9009,
            'baseTotal' => 10010,
            'tax' => 0,
            'discount' => [
                'type' => 'percentage',
                'value' => 10,
            ],
            'terms' => null,
            'notes' => null,
        ], $result);
    }

    public function testDelete(): void
    {
        $recurringInvoice = RecurringInvoiceFactory::createOne()->_real();

        $this->requestDelete($this->getIriFromResource($recurringInvoice));
    }

    public function testGet(): void
    {
        $client = ClientFactory::createOne();
        $contacts = ContactFactory::createMany($this->faker->numberBetween(1, 5), ['client' => $client]);

        /** @var RecurringInvoice $recurringInvoice */
        $recurringInvoice = RecurringInvoiceFactory::createOne([
            'users' => $contacts,
            'frequency' => '* * * * *',
            'lines' => [
                (new RecurringInvoiceLine())
                    ->setDescription('Test Line')
                    ->setPrice(100)
                    ->setQty(1)
            ],
            'discount' => (new Discount())
                ->setType('percentage')
                ->setValue(0),
        ])->_real();

        $data = $this->requestGet($this->getIriFromResource($recurringInvoice));

        self::assertEqualsCanonicalizing([
            '@context' => '/api/contexts/RecurringInvoice',
            '@id' => $this->getIriFromResource($recurringInvoice),
            '@type' => 'RecurringInvoice',
            'id' => $recurringInvoice->getId()->toString(),
            'client' => '/api/clients/' . $recurringInvoice->getClient()->getId()->toString(),
            'frequency' => '* * * * *',
            'dateStart' => $recurringInvoice->getDateStart()->format('c'),
            'dateEnd' => null,
            'lines' => [
                [
                    '@id' => $this->getIriFromResource($recurringInvoice->getLines()->first()),
                    '@type' => 'RecurringInvoiceLine',
                    'id' => $recurringInvoice->getLines()->first()->getId()->toString(),
                    'description' => 'Test Line',
                    'price' => 1,
                    'qty' => 1,
                    'tax' => null,
                    'total' => 1,
                ],
            ],
            'users' => array_map(fn (Proxy $contact) => $this->getIriFromResource($contact->_real()), $contacts),
            'status' => $recurringInvoice->getStatus(),
            'total' => 1,
            'baseTotal' => 1,
            'tax' => 0,
            'discount' => [
                'type' => $recurringInvoice->getDiscount()->getType(),
                'value' => 0,
            ],
            'terms' => $recurringInvoice->getTerms(),
            'notes' => $recurringInvoice->getNotes(),
        ], $data);
    }

    public function testEdit(): void
    {
        $client = ClientFactory::createOne();
        $contacts = ContactFactory::createMany($this->faker->numberBetween(1, 5), ['client' => $client]);

        /** @var RecurringInvoice $recurringInvoice */
        $recurringInvoice = RecurringInvoiceFactory::createOne([
            'users' => $contacts,
            'frequency' => '* * * * *',
            'lines' => [
                (new RecurringInvoiceLine())
                    ->setDescription('Test Line')
                    ->setPrice(100)
                    ->setQty(1)
            ],
            'discount' => (new Discount())
                ->setType('percentage')
                ->setValue(0),
        ])->_real();

        $data = $this->requestPatch(
            $this->getIriFromResource($recurringInvoice),
            [
                'frequency' => '5 * * * *',
                'dateStart' => '2012-01-01',
                'discount' => [
                    'type' => 'percentage',
                    'value' => 10.0,
                ],
                'lines' => [
                    [
                        'price' => 100.0,
                        'qty' => 1.0,
                        'description' => 'Foo Line',
                    ],
                ],
            ]
        );

        self::assertEqualsCanonicalizing([
            '@context' => '/api/contexts/RecurringInvoice',
            '@id' => $this->getIriFromResource($recurringInvoice),
            '@type' => 'RecurringInvoice',
            'id' => $recurringInvoice->getId()->toString(),
            'client' => $this->getIriFromResource($recurringInvoice->getClient()),
            'frequency' => '5 * * * *',
            'dateStart' => '2012-01-01T00:00:00+02:00',
            'dateEnd' => null,
            'lines' => [
                [
                    '@id' => $this->getIriFromResource($recurringInvoice->getLines()->first()),
                    '@type' => 'RecurringInvoiceLine',
                    'id' => $recurringInvoice->getLines()->first()->getId()->toString(),
                    'description' => 'Foo Line',
                    'price' => 100,
                    'qty' => 1,
                    'tax' => null,
                    'total' => 100,
                ],
            ],
            'users' => array_map(fn (Proxy $contact) => $this->getIriFromResource($contact->_real()), $contacts),
            'status' => $recurringInvoice->getStatus(),
            'total' => 90,
            'baseTotal' => 100,
            'tax' => 0,
            'discount' => [
                'type' => 'percentage',
                'value' => 10,
            ],
            'terms' => $recurringInvoice->getTerms(),
            'notes' => $recurringInvoice->getNotes(),
        ], $data);
    }
}
