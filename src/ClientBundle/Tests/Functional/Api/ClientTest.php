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

use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use SolidInvoice\ApiBundle\Test\ApiTestCase;
use SolidInvoice\ClientBundle\DataFixtures\ORM\LoadData;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;

/**
 * @group functional
 */
class ClientTest extends ApiTestCase
{

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        $databaseTool = static::getContainer()->get(DatabaseToolCollection::class)->get();

        $databaseTool->loadFixtures([
            LoadData::class,
        ], true);
    }

    public function testCreate(): void
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

        self::assertSame([
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

    public function testDelete(): void
    {
        $this->requestDelete('/api/clients/1');
    }

    public function testGet(): void
    {
        $data = $this->requestGet('/api/clients/1');

        self::assertSame([
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

    public function testEdit(): void
    {
        $data = $this->requestPut('/api/clients/1', ['name' => 'New Test']);

        self::assertSame([
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
