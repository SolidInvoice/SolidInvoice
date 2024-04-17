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

use Doctrine\Common\DataFixtures\Executor\AbstractExecutor;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Ramsey\Uuid\Uuid;
use SolidInvoice\ApiBundle\Test\ApiTestCase;
use SolidInvoice\ClientBundle\DataFixtures\ORM\LoadData as LoadClientData;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\QuoteBundle\DataFixtures\ORM\LoadData as LoadQuoteData;
use SolidInvoice\QuoteBundle\Entity\Quote;
use function assert;

/**
 * @group functional
 */
final class QuoteTest extends ApiTestCase
{
    private AbstractExecutor $executor;

    protected function setUp(): void
    {
        parent::setUp();

        $databaseTool = self::getContainer()->get(DatabaseToolCollection::class)->get();

        $this->executor = $databaseTool->loadFixtures([
            LoadClientData::class,
            LoadQuoteData::class,
        ], true);
    }

    public function testCreate(): void
    {
        $contact = $this->executor->getReferenceRepository()->getReference('contact');
        assert($contact instanceof Contact);

        $data = [
            'users' => [
                '/api/contacts/' . $contact->getId(),
            ],
            'client' => '/api/clients/' . $contact->getClient()->getId(),
            'discount' => [
                'type' => 'percentage',
                'value' => 10,
            ],
            'items' => [
                [
                    'price' => 100,
                    'qty' => 1,
                    'description' => 'Foo Item',
                ],
            ],
        ];

        $result = $this->requestPost('/api/quotes', $data);

        self::assertTrue(Uuid::isValid($result['id']));
        self::assertTrue(Uuid::isValid($result['uuid']));
        self::assertTrue(Uuid::isValid($result['items'][0]['id']));

        unset($result['id'], $result['uuid'], $result['items'][0]['id']);

        self::assertSame([
            'status' => 'draft',
            'client' => '/api/clients/' . $contact->getClient()->getId(),
            'total' => '$90.00',
            'baseTotal' => '$100.00',
            'tax' => '$0.00',
            'discount' => [
                'type' => 'percentage',
                'value' => 10,
            ],
            'terms' => null,
            'notes' => null,
            'due' => null,
            'items' => [
                [
                    'description' => 'Foo Item',
                    'price' => '$100.00',
                    'qty' => 1,
                    'tax' => null,
                    'total' => '$100.00',
                ],
            ],
            'users' => [
                '/api/contacts/' . $contact->getId(),
            ],
        ], $result);
    }

    public function testDelete(): void
    {
        $quote = $this->executor->getReferenceRepository()->getReference('quote');
        assert($quote instanceof Quote);

        $this->requestDelete('/api/quotes/' . $quote->getId());
    }

    public function testGet(): void
    {
        $quote = $this->executor->getReferenceRepository()->getReference('quote');
        assert($quote instanceof Quote);

        $data = $this->requestGet('/api/quotes/' . $quote->getId());

        self::assertSame([
            'id' => $quote->getId()->toString(),
            'uuid' => $quote->getUuid()->toString(),
            'status' => 'draft',
            'client' => '/api/clients/' . $quote->getClient()->getId(),
            'total' => '$100.00',
            'baseTotal' => '$100.00',
            'tax' => '$0.00',
            'discount' => [
                'type' => null,
                'value' => null,
            ],
            'terms' => null,
            'notes' => null,
            'due' => null,
            'items' => [
                [
                    'id' => $quote->getItems()->first()->getId()->toString(),
                    'description' => 'Test Item',
                    'price' => '$100.00',
                    'qty' => 1,
                    'tax' => null,
                    'total' => '$100.00',
                ],
            ],
            'users' => [
                '/api/contacts/' . $quote->getUsers()->first()->getId(),
            ],
        ], $data);
    }

    public function testEdit(): void
    {
        $quote = $this->executor->getReferenceRepository()->getReference('quote');
        assert($quote instanceof Quote);

        $data = $this->requestPut(
            '/api/quotes/' . $quote->getId()->toString(),
            [
                'discount' => [
                    'type' => 'percentage',
                    'value' => 10,
                ],
                'items' => [
                    [
                        'price' => 100,
                        'qty' => 1,
                        'description' => 'Foo Item',
                    ],
                ],
            ]
        );

        self::assertSame([
            'id' => $quote->getId()->toString(),
            'uuid' => $quote->getUuid()->toString(),
            'status' => 'draft',
            'client' => '/api/clients/' . $quote->getClient()->getId(),
            'total' => '$90.00',
            'baseTotal' => '$100.00',
            'tax' => '$0.00',
            'discount' => [
                'type' => 'percentage',
                'value' => 10,
            ],
            'terms' => null,
            'notes' => null,
            'due' => null,
            'items' => [
                [
                    'id' => $quote->getItems()->first()->getId()->toString(),
                    'description' => 'Foo Item',
                    'price' => '$100.00',
                    'qty' => 1,
                    'tax' => null,
                    'total' => '$100.00',
                ],
            ],
            'users' => [
                '/api/contacts/' . $quote->getUsers()->first()->getId(),
            ],
        ], $data);
    }
}
