<?php

declare(strict_types=1);

/*
 * This file is part of the SolidInvoice project.
 *
 * @author     pierre
 * @copyright  Copyright (c) 2019
 */

namespace SolidInvoice\InvoiceBundle\Tests\Functional\Api;

use DAMA\DoctrineTestBundle\Doctrine\DBAL\StaticDriver;
use Liip\TestFixturesBundle\Test\FixturesTrait;
use SolidInvoice\ApiBundle\Test\ApiTestCase;

/**
 * @group functional
 */
class RecurringInvoiceTest extends ApiTestCase
{
    use FixturesTrait;

    public function setUp(): void
    {
        parent::setUp();

        StaticDriver::rollBack();
        $connection = self::bootKernel()->getContainer()->get('doctrine')->getConnection();
        $connection->executeQuery('ALTER TABLE clients AUTO_INCREMENT = 1000');
        $connection->executeQuery('ALTER TABLE contacts AUTO_INCREMENT = 1000');
        $connection->executeQuery('ALTER TABLE recurring_invoices AUTO_INCREMENT = 1000');
        $connection->executeQuery('ALTER TABLE invoice_lines AUTO_INCREMENT = 1000');
        StaticDriver::beginTransaction();

        $this->loadFixtures([
            'SolidInvoice\ClientBundle\DataFixtures\ORM\LoadData',
            'SolidInvoice\InvoiceBundle\DataFixtures\ORM\LoadData',
        ], true);
    }

    public function testCreate()
    {
        $date = date(\DateTimeInterface::ATOM);

        $data = [
            'users' => [
                '/api/contacts/1000',
            ],
            'client' => '/api/clients/1000',
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

        unset($result['uuid']);

        static::assertEquals([
            'id' => 1001,
            'status' => 'draft',
            'client' => '/api/clients/1000',
            'frequency' => '* * * * *',
            'dateStart' => $date,
            'dateEnd' => null,
            'total' => '$90.00',
            'baseTotal' => '$100.00',
            'balance' => '$90.00',
            'tax' => '$0.00',
            'discount' => [
                'type' => 'percentage',
                'value' => 10,
            ],
            'terms' => null,
            'notes' => null,
            'due' => null,
            'paidDate' => null,
            'items' => [
                [
                    'id' => 1001,
                    'description' => 'Foo Item',
                    'price' => '$100.00',
                    'qty' => 1,
                    'tax' => null,
                    'total' => '$100.00',
                ],
            ],
            'users' => [
                '/api/contacts/1000',
            ],
        ], $result);
    }

    public function testDelete()
    {
        $this->requestDelete('/api/recurring_invoices/1000');
    }

    public function testGet()
    {
        $data = $this->requestGet('/api/recurring_invoices/1000');

        unset($data['uuid']);

        static::assertEquals([
            'id' => 1000,
            'status' => 'draft',
            'client' => '/api/clients/1000',
            'frequency' => '* * * * *',
            'dateStart' => '2012-01-01 15:30:00',
            'dateEnd' => null,
            'total' => '$100.00',
            'baseTotal' => '$100.00',
            'balance' => '$100.00',
            'tax' => '$0.00',
            'discount' => [
                'type' => null,
                'value' => null,
            ],
            'terms' => null,
            'notes' => null,
            'due' => null,
            'paidDate' => null,
            'items' => [
                [
                    'id' => 1000,
                    'description' => 'Test Item',
                    'price' => '$100.00',
                    'qty' => 1,
                    'tax' => null,
                    'total' => '$100.00',
                ],
            ],
            'users' => [
                '/api/contacts/1000',
            ],
        ], $data);
    }

    public function testEdit()
    {
        $data = $this->requestPut(
            '/api/recurring_invoices/1000',
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

        unset($data['uuid']);

        static::assertEquals([
            'id' => 1000,
            'status' => 'draft',
            'client' => '/api/clients/1000',
            'frequency' => '5 * * * *',
            'dateStart' => '2012-01-01 15:30:00',
            'dateEnd' => null,
            'total' => '$90.00',
            'baseTotal' => '$100.00',
            'balance' => '$90.00',
            'tax' => '$0.00',
            'discount' => [
                'type' => 'percentage',
                'value' => 10,
            ],
            'terms' => null,
            'notes' => null,
            'due' => null,
            'paidDate' => null,
            'items' => [
                [
                    'id' => 1001,
                    'description' => 'Foo Item',
                    'price' => '$100.00',
                    'qty' => 1,
                    'tax' => null,
                    'total' => '$100.00',
                ],
            ],
            'users' => [
                '/api/contacts/1000',
            ],
        ], $data);
    }
}
