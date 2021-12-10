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

use DAMA\DoctrineTestBundle\Doctrine\DBAL\StaticDriver;
use Liip\TestFixturesBundle\Test\FixturesTrait;
use SolidInvoice\ApiBundle\Test\ApiTestCase;
use SolidInvoice\CoreBundle\Test\Traits\DatabaseTestCase;

/**
 * @group functional
 */
class QuoteTest extends ApiTestCase
{
    use DatabaseTestCase;
    use FixturesTrait;

    public function setUp(): void
    {
        parent::setUp();
        StaticDriver::rollBack();
        $connection = self::bootKernel()->getContainer()->get('doctrine')->getConnection();
        $connection->executeQuery('ALTER TABLE clients AUTO_INCREMENT = 1000');
        $connection->executeQuery('ALTER TABLE contacts AUTO_INCREMENT = 1000');
        $connection->executeQuery('ALTER TABLE quotes AUTO_INCREMENT = 1000');
        $connection->executeQuery('ALTER TABLE quote_lines AUTO_INCREMENT = 1000');
        StaticDriver::beginTransaction();

        $this->loadFixtures([
            'SolidInvoice\ClientBundle\DataFixtures\ORM\LoadData',
            'SolidInvoice\QuoteBundle\DataFixtures\ORM\LoadData',
        ], true);
    }

    public function testCreate()
    {
        $data = [
            'users' => [
                '/api/contacts/1000',
            ],
            'client' => '/api/clients/1000',
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

        unset($result['uuid']);

        static::assertSame([
            'id' => 1001,
            'status' => 'draft',
            'client' => '/api/clients/1000',
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
        $this->requestDelete('/api/quotes/1000');
    }

    public function testGet()
    {
        $data = $this->requestGet('/api/quotes/1000');

        unset($data['uuid']);

        static::assertSame([
            'id' => 1000,
            'status' => 'draft',
            'client' => '/api/clients/1000',
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
            '/api/quotes/1000',
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

        unset($data['uuid']);

        static::assertSame([
            'id' => 1000,
            'status' => 'draft',
            'client' => '/api/clients/1000',
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
