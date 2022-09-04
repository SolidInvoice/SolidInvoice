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

namespace SolidInvoice\ClientBundle\Tests\Functional\Api;

use SolidInvoice\ClientBundle\DataFixtures\ORM\LoadData;
use Liip\TestFixturesBundle\Test\FixturesTrait;
use SolidInvoice\ApiBundle\Test\ApiTestCase;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;

/**
 * @group functional
 */
class ClientTest extends ApiTestCase
{
    use EnsureApplicationInstalled;
    use FixturesTrait;

    public function setUp(): void
    {
        parent::setUp();

        $this->loadFixtures([
            LoadData::class,
        ], true);
    }

    public function testCreate()
    {
        $data = [
            'name' => 'Dummy User',
            'contacts' => [
                [
                    'firstName' => 'foo bar',
                    'email' => 'foo@example.com',
                ],
            ],
        ];

        $result = $this->requestPost('/api/clients', $data);

        static::assertSame([
            'id' => 2,
            'name' => 'Dummy User',
            'website' => null,
            'status' => 'active',
            'currency' => null,
            'vatNumber' => null,
            'contacts' => [
                [
                    'id' => 2,
                    'firstName' => 'foo bar',
                    'lastName' => null,
                    'email' => 'foo@example.com',
                    'additionalContactDetails' => [],
                ],
            ],
            'addresses' => [],
            'credit' => '$0.00',
        ], $result);
    }

    public function testDelete()
    {
        $this->requestDelete('/api/clients/1');
    }

    public function testGet()
    {
        $data = $this->requestGet('/api/clients/1');

        static::assertSame([
            'id' => 1,
            'name' => 'Test',
            'website' => null,
            'status' => 'active',
            'currency' => null,
            'vatNumber' => null,
            'contacts' => [
                [
                    'id' => 1,
                    'firstName' => 'Test',
                    'lastName' => null,
                    'email' => 'test@example.com',
                    'additionalContactDetails' => [],
                ],
            ],
            'addresses' => [],
            'credit' => '$0.00',
        ], $data);
    }

    public function testEdit()
    {
        $data = $this->requestPut('/api/clients/1', ['name' => 'New Test']);

        static::assertSame([
            'id' => 1,
            'name' => 'New Test',
            'website' => null,
            'status' => 'active',
            'currency' => null,
            'vatNumber' => null,
            'contacts' => [
                [
                    'id' => 1,
                    'firstName' => 'Test',
                    'lastName' => null,
                    'email' => 'test@example.com',
                    'additionalContactDetails' => [],
                ],
            ],
            'addresses' => [],
            'credit' => '$0.00',
        ], $data);
    }
}
