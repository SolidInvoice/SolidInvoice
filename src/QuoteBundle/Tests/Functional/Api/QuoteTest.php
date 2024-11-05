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

namespace SolidInvoice\QuoteBundle\Tests\Functional\Api;

use DateTime;
use SolidInvoice\ApiBundle\Test\ApiTestCase;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\ClientBundle\Test\Factory\ContactFactory;
use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\QuoteBundle\Entity\Line;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Test\Factory\QuoteFactory;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Uid\Uuid;
use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Test\Factories;
use function array_map;

/**
 * @group functional
 */
final class QuoteTest extends ApiTestCase
{
    use Factories;

    protected function getResourceClass(): string
    {
        return Quote::class;
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

        $result = $this->requestPost('/api/quotes', $data);

        self::assertTrue(Ulid::isValid($result['id']));
        self::assertTrue(Uuid::isValid($result['uuid']));
        self::assertTrue(Ulid::isValid($result['lines'][0]['id']));

        self::assertJsonContains([
            '@context' => $this->getContextForResource($this->getResourceClass()),
            '@type' => 'Quote',
            'client' => $this->getIriFromResource($client),
            'due' => null,
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
        $quote = QuoteFactory::createOne(['client' => $client])->_real();

        $this->requestDelete($this->getIriFromResource($quote));
    }

    public function testGet(): void
    {
        $client = ClientFactory::createOne();
        $contacts = ContactFactory::createMany($this->faker->numberBetween(1, 5), ['client' => $client]);

        /** @var Quote $quote */
        $quote = QuoteFactory::createOne([
            'client' => $client,
            'users' => $contacts,
            'status' => 'draft',
            'due' => new DateTime('2005-01-20'),
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

        $data = $this->requestGet($this->getIriFromResource($quote));

        self::assertEqualsCanonicalizing([
            '@context' => '/api/contexts/Quote',
            '@id' => $this->getIriFromResource($quote),
            '@type' => 'Quote',
            'id' => $quote->getId()->toString(),
            'quoteId' => '',
            'uuid' => $quote->getUuid()->toString(),
            'status' => 'draft',
            'client' => '/api/clients/' . $quote->getClient()->getId(),
            'total' => 100,
            'baseTotal' => 100,
            'tax' => 0,
            'discount' => [
                'type' => null,
                'value' => 0,
            ],
            'terms' => $quote->getTerms(),
            'notes' => $quote->getNotes(),
            'due' => '2005-01-20T00:00:00+02:00',
            'lines' => [
                [
                    '@id' => $this->getIriFromResource($quote->getLines()->first()),
                    '@type' => 'QuoteLine',
                    'id' => $quote->getLines()->first()->getId()->toString(),
                    'description' => 'Test Item',
                    'price' => 100,
                    'qty' => 1,
                    'tax' => null,
                    'total' => 100,
                ],
            ],
            'users' => array_map(fn (Proxy $contact) => $this->getIriFromResource($contact->_real()), $contacts),
            'invoice' => null,
        ], $data);
    }

    public function testEdit(): void
    {
        $client = ClientFactory::createOne();
        $contacts = ContactFactory::createMany($this->faker->numberBetween(1, 5), ['client' => $client]);

        /** @var Quote $quote */
        $quote = QuoteFactory::createOne([
            'client' => $client,
            'users' => $contacts,
            'status' => 'draft',
            'due' => new DateTime('2005-01-20'),
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

        $data = $this->requestPatch(
            $this->getIriFromResource($quote),
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
                    [
                        'price' => 500,
                        'qty' => 5,
                        'description' => 'Foo Items',
                    ],
                ],
            ]
        );

        self::assertEqualsCanonicalizing([
            '@context' => '/api/contexts/Quote',
            '@id' => $this->getIriFromResource($quote),
            '@type' => 'Quote',
            'id' => $quote->getId()->toString(),
            'quoteId' => '',
            'uuid' => $quote->getUuid()->toString(),
            'status' => 'draft',
            'client' => '/api/clients/' . $quote->getClient()->getId(),
            'total' => 11250,
            'baseTotal' => 12500,
            'tax' => 0,
            'discount' => [
                'type' => 'percentage',
                'value' => 10,
            ],
            'terms' => $quote->getTerms(),
            'notes' => $quote->getNotes(),
            'due' => '2005-01-20T00:00:00+02:00',
            'lines' => [
                [
                    '@id' => $this->getIriFromResource($quote->getLines()->get(0)),
                    '@type' => 'QuoteLine',
                    'id' => $quote->getLines()->get(0)->getId()->toString(),
                    'description' => 'Foo Item',
                    'price' => 10000,
                    'qty' => 1,
                    'tax' => null,
                    'total' => 10000,
                ],
                [
                    '@id' => $this->getIriFromResource($quote->getLines()->get(1)),
                    '@type' => 'QuoteLine',
                    'id' => $quote->getLines()->get(1)->getId()->toString(),
                    'description' => 'Foo Items',
                    'price' => 500,
                    'qty' => 5,
                    'tax' => null,
                    'total' => 2500,
                ],
            ],
            'users' => array_map(fn (Proxy $contact) => $this->getIriFromResource($contact->_real()), $contacts),
            'invoice' => null,
        ], $data);
    }
}
