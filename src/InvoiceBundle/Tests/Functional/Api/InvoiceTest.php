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

use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Ramsey\Uuid\Uuid;
use SolidInvoice\ApiBundle\Test\ApiTestCase;
use SolidInvoice\ClientBundle\DataFixtures\ORM\LoadData as LoadClientData;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\InvoiceBundle\DataFixtures\ORM\LoadData as LoadInvoiceData;

/**
 * @group functional
 */
final class InvoiceTest extends ApiTestCase
{
    use EnsureApplicationInstalled;

    protected function setUp(): void
    {
        parent::setUp();

        $databaseTool = self::getContainer()->get(DatabaseToolCollection::class)->get();

        $databaseTool->loadFixtures([
            LoadClientData::class,
            LoadInvoiceData::class,
        ], true);
    }

    public function testCreate(): void
    {
        $data = [
            'users' => [
                '/api/contacts/1',
            ],
            'client' => '/api/clients/1',
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

        $result = $this->requestPost('/api/invoices', $data);

        self::assertTrue(Uuid::isValid($result['uuid']));

        unset($result['uuid']);

        self::assertSame([
            'id' => 2,
            'client' => '/api/clients/1',
            'balance' => '$90.00',
            'due' => null,
            'paidDate' => null,
            'items' => [
                [
                    'id' => 3,
                    'description' => 'Foo Item',
                    'price' => '$100.00',
                    'qty' => 1,
                    'tax' => null,
                    'total' => '$100.00',
                ],
            ],
            'users' => [
                '/api/contacts/1',
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
        $this->requestDelete('/api/invoices/1');
    }

    public function testGet(): void
    {
        $data = $this->requestGet('/api/invoices/1');

        self::assertTrue(Uuid::isValid($data['uuid']));

        unset($data['uuid']);

        self::assertSame([
            'id' => 1,
            'client' => '/api/clients/1',
            'balance' => '$100.00',
            'due' => null,
            'paidDate' => null,
            'items' => [
                [
                    'id' => 1,
                    'description' => 'Test Item',
                    'price' => '$100.00',
                    'qty' => 1,
                    'tax' => null,
                    'total' => '$100.00',
                ],
            ],
            'users' => [
                '/api/contacts/1',
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
        $data = $this->requestPut(
            '/api/invoices/1',
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

        self::assertTrue(Uuid::isValid($data['uuid']));

        unset($data['uuid']);

        self::assertSame([
            'id' => 1,
            'client' => '/api/clients/1',
            'balance' => '$90.00',
            'due' => null,
            'paidDate' => null,
            'items' => [
                [
                    'id' => 3,
                    'description' => 'Foo Item',
                    'price' => '$100.00',
                    'qty' => 1,
                    'tax' => null,
                    'total' => '$100.00',
                ],
            ],
            'users' => [
                '/api/contacts/1',
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
