<?php

declare(strict_types=1);

/*
 * This file is part of the SolidInvoice project.
 *
 * @author     pierre
 * @copyright  Copyright (c) 2019
 */

namespace SolidInvoice\ClientBundle\Tests\Functional\Api;

use DAMA\DoctrineTestBundle\Doctrine\DBAL\StaticDriver;
use Liip\TestFixturesBundle\Test\FixturesTrait;
use SolidInvoice\ApiBundle\Test\ApiTestCase;

/**
 * @group functional
 */
class ClientTest extends ApiTestCase
{
    use FixturesTrait;

    public function setUp(): void
    {
        parent::setUp();
        StaticDriver::rollBack();
        $connection = self::bootKernel()->getContainer()->get('doctrine')->getConnection();
        $connection->executeQuery('ALTER TABLE clients AUTO_INCREMENT = 1000');
        $connection->executeQuery('ALTER TABLE contacts AUTO_INCREMENT = 1000');
        StaticDriver::beginTransaction();

        $this->loadFixtures([
            'SolidInvoice\ClientBundle\DataFixtures\ORM\LoadData',
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

        $this->assertSame(
            [
                'id' => 1001,
                'name' => 'Dummy User',
                'website' => null,
                'status' => 'active',
                'currency' => null,
                'vatNumber' => null,
                'contacts' => [
                    [
                        'id' => 1001,
                        'firstName' => 'foo bar',
                        'lastName' => null,
                        'email' => 'foo@example.com',
                        'additionalContactDetails' => [],
                    ],
                ],
                'addresses' => [],
                'credit' => '$0.00',
            ],
            $result
        );
    }

    public function testDelete()
    {
        $this->requestDelete('/api/clients/1000');
    }

    public function testGet()
    {
        $data = $this->requestGet('/api/clients/1000');

        $this->assertSame(
            [
                'id' => 1000,
                'name' => 'Test',
                'website' => null,
                'status' => 'active',
                'currency' => null,
                'vatNumber' => null,
                'contacts' => [
                    [
                        'id' => 1000,
                        'firstName' => 'Test',
                        'lastName' => null,
                        'email' => 'test@example.com',
                        'additionalContactDetails' => [],
                    ],
                ],
                'addresses' => [],
                'credit' => '$0.00',
            ],
            $data
        );
    }

    public function testEdit()
    {
        $data = $this->requestPut('/api/clients/1000', ['name' => 'New Test']);

        $this->assertSame(
            [
                'id' => 1000,
                'name' => 'New Test',
                'website' => null,
                'status' => 'active',
                'currency' => null,
                'vatNumber' => null,
                'contacts' => [
                    [
                        'id' => 1000,
                        'firstName' => 'Test',
                        'lastName' => null,
                        'email' => 'test@example.com',
                        'additionalContactDetails' => [],
                    ],
                ],
                'addresses' => [],
                'credit' => '$0.00',
            ],
            $data
        );
    }
}
