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
use Doctrine\Common\DataFixtures\Executor\AbstractExecutor;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Ramsey\Uuid\Uuid;
use SolidInvoice\ApiBundle\Test\ApiTestCase;
use SolidInvoice\ClientBundle\DataFixtures\ORM\LoadData as LoadClientData;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\InvoiceBundle\DataFixtures\ORM\LoadData as LoadInvoiceData;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use function assert;

/**
 * @group functional
 */
final class RecurringInvoiceTest extends ApiTestCase
{
    private AbstractExecutor $executor;

    protected function setUp(): void
    {
        parent::setUp();

        $databaseTool = self::getContainer()->get(DatabaseToolCollection::class)->get();

        $this->executor = $databaseTool->loadFixtures([
            LoadClientData::class,
            LoadInvoiceData::class,
        ], true);
    }

    public function testCreate(): void
    {
        $contact = $this->executor->getReferenceRepository()->getReference('contact');
        assert($contact instanceof Contact);

        $date = date(DateTimeInterface::ATOM);

        $data = [
            'users' => [
                '/api/contacts/' . $contact->getId(),
            ],
            'client' => '/api/clients/' . $contact->getClient()->getId(),
            'frequency' => '* * * * *',
            'dateStart' => $date,
            'dateEnd' => null,
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

        $result = $this->requestPost('/api/recurring_invoices', $data);

        self::assertArrayHasKey('id', $result);
        self::assertArrayHasKey('id', $result['items'][0]);
        self::assertTrue(Uuid::isValid($result['id']));
        self::assertTrue(Uuid::isValid($result['items'][0]['id']));

        unset($result['id'], $result['items'][0]['id']);

        self::assertSame([
            'client' => '/api/clients/' . $contact->getClient()->getId(),
            'frequency' => '* * * * *',
            'dateStart' => date('Y-m-d\T00:00:00+02:00'),
            'dateEnd' => null,
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
            'status' => 'draft',
            'total' => '$90.00',
            'baseTotal' => '$100.00',
            'tax' => '$0.00',
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
        $recurringInvoice = $this->executor->getReferenceRepository()->getReference('recurringInvoice');
        assert($recurringInvoice instanceof RecurringInvoice);

        $this->requestDelete('/api/recurring_invoices/' . $recurringInvoice->getId());
    }

    public function testGet(): void
    {
        $recurringInvoice = $this->executor->getReferenceRepository()->getReference('recurringInvoice');
        assert($recurringInvoice instanceof RecurringInvoice);

        $data = $this->requestGet('/api/recurring_invoices/' . $recurringInvoice->getId());

        self::assertSame([
            'id' => $recurringInvoice->getId()->toString(),
            'client' => '/api/clients/' . $recurringInvoice->getClient()->getId()->toString(),
            'frequency' => '* * * * *',
            'dateStart' => '2012-01-01T00:00:00+02:00',
            'dateEnd' => null,
            'items' => [
                [
                    'id' => $recurringInvoice->getItems()->first()->getId()->toString(),
                    'description' => 'Test Item',
                    'price' => '$100.00',
                    'qty' => 1,
                    'tax' => null,
                    'total' => '$100.00',
                ],
            ],
            'users' => [
                '/api/contacts/' . $recurringInvoice->getUsers()->first()->getId()->toString(),
            ],
            'status' => 'draft',
            'total' => '$100.00',
            'baseTotal' => '$100.00',
            'tax' => '$0.00',
            'discount' => [
                'type' => null,
                'value' => null,
            ],
            'terms' => null,
            'notes' => null,
        ], $data);
    }

    public function testEdit(): void
    {
        $recurringInvoice = $this->executor->getReferenceRepository()->getReference('recurringInvoice');
        assert($recurringInvoice instanceof RecurringInvoice);

        $data = $this->requestPut(
            '/api/recurring_invoices/' . $recurringInvoice->getId(),
            [
                'frequency' => '5 * * * *',
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
            'id' => $recurringInvoice->getId()->toString(),
            'client' => '/api/clients/' . $recurringInvoice->getClient()->getId()->toString(),
            'frequency' => '5 * * * *',
            'dateStart' => '2012-01-01T00:00:00+02:00',
            'dateEnd' => null,
            'items' => [
                [
                    'id' => $recurringInvoice->getItems()->first()->getId()->toString(),
                    'description' => 'Foo Item',
                    'price' => '$100.00',
                    'qty' => 1,
                    'tax' => null,
                    'total' => '$100.00',
                ],
            ],
            'users' => [
                '/api/contacts/' . $recurringInvoice->getUsers()->first()->getId()->toString(),
            ],
            'status' => 'draft',
            'total' => '$90.00',
            'baseTotal' => '$100.00',
            'tax' => '$0.00',
            'discount' => [
                'type' => 'percentage',
                'value' => 10,
            ],
            'terms' => null,
            'notes' => null,
        ], $data);
    }
}
