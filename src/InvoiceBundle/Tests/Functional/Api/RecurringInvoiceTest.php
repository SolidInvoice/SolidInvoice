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

    protected function getResourceClass(): string
    {
        return RecurringInvoice::class;
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

        $result = $this->requestPost('/api/recurring_invoices', $data);

        self::assertArrayHasKey('id', $result);
        self::assertArrayHasKey('id', $result['lines'][0]);
        self::assertTrue(Uuid::isValid($result['id']));
        self::assertTrue(Uuid::isValid($result['lines'][0]['id']));

        unset($result['id'], $result['lines'][0]['id']);

        self::assertSame([
            'client' => '/api/clients/' . $contact->getClient()->getId(),
            'frequency' => '* * * * *',
            'dateStart' => date('Y-m-d\T00:00:00+02:00'),
            'dateEnd' => null,
            'lines' => [
                [
                    'description' => 'Foo Line',
                    'price' => 100.1,
                    'qty' => 1.0,
                    'tax' => null,
                    'total' => 100.1,
                ],
            ],
            'users' => [
                '/api/contacts/' . $contact->getId(),
            ],
            'status' => 'draft',
            'total' => 90.09,
            'baseTotal' => 100.1,
            'tax' => 0.0,
            'discount' => [
                'type' => 'percentage',
                'value' => 10.0,
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
            'lines' => [
                [
                    'id' => $recurringInvoice->getLines()->first()->getId()->toString(),
                    'description' => 'Test Line',
                    'price' => 100.0,
                    'qty' => 1.0,
                    'tax' => null,
                    'total' => 100.0,
                ],
            ],
            'users' => [
                '/api/contacts/' . $recurringInvoice->getUsers()->first()->getId()->toString(),
            ],
            'status' => 'draft',
            'total' => 100.0,
            'baseTotal' => 100.0,
            'tax' => 0.0,
            'discount' => [
                'type' => null,
                'value' => 0.0,
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

        self::assertSame([
            'id' => $recurringInvoice->getId()->toString(),
            'client' => '/api/clients/' . $recurringInvoice->getClient()->getId()->toString(),
            'frequency' => '5 * * * *',
            'dateStart' => '2012-01-01T00:00:00+02:00',
            'dateEnd' => null,
            'lines' => [
                [
                    'id' => $recurringInvoice->getLines()->first()->getId()->toString(),
                    'description' => 'Foo Line',
                    'price' => 100.0,
                    'qty' => 1.0,
                    'tax' => null,
                    'total' => 100.0,
                ],
            ],
            'users' => [
                '/api/contacts/' . $recurringInvoice->getUsers()->first()->getId()->toString(),
            ],
            'status' => 'draft',
            'total' => 90.0,
            'baseTotal' => 100.0,
            'tax' => 0.0,
            'discount' => [
                'type' => 'percentage',
                'value' => 10.0,
            ],
            'terms' => null,
            'notes' => null,
        ], $data);
    }
}
