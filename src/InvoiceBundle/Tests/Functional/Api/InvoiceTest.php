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

use SolidInvoice\ClientBundle\DataFixtures\ORM\LoadData;
use Liip\TestFixturesBundle\Test\FixturesTrait;
use SolidInvoice\ApiBundle\Test\ApiTestCase;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;

/**
 * @group functional
 */
class InvoiceTest extends ApiTestCase
{
    use FixturesTrait;
    use EnsureApplicationInstalled;

    public function setUp(): void
    {
        parent::setUp();

        $this->loadFixtures([
            LoadData::class,
            \SolidInvoice\InvoiceBundle\DataFixtures\ORM\LoadData::class,
        ], true);
    }

    public function testCreate()
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

        unset($result['uuid']);

        static::assertSame([
            'id' => 2,
            'status' => 'draft',
            'client' => '/api/clients/1',
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
        ], $result);
    }

    public function testDelete()
    {
        $this->requestDelete('/api/invoices/1');
    }

    public function testGet()
    {
        $data = $this->requestGet('/api/invoices/1');

        unset($data['uuid']);

        static::assertSame([
            'id' => 1,
            'status' => 'draft',
            'client' => '/api/clients/1',
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
        ], $data);
    }

    public function testEdit()
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

        unset($data['uuid']);

        static::assertSame([
            'id' => 1,
            'status' => 'draft',
            'client' => '/api/clients/1',
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
        ], $data);
    }
}
