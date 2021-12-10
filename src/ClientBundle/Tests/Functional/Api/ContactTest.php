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

use DAMA\DoctrineTestBundle\Doctrine\DBAL\StaticDriver;
use Liip\TestFixturesBundle\Test\FixturesTrait;
use SolidInvoice\ApiBundle\Test\ApiTestCase;
use SolidInvoice\CoreBundle\Test\Traits\DatabaseTestCase;

/**
 * @group functional
 */
class ContactTest extends ApiTestCase
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
        StaticDriver::beginTransaction();

        $this->loadFixtures([
            'SolidInvoice\ClientBundle\DataFixtures\ORM\LoadData',
        ], true);
    }

    public function testCreate()
    {
        $data = [
            'client' => '/api/clients/1000',
            'firstName' => 'foo bar',
            'email' => 'foo@bar.com',
        ];

        $result = $this->requestPost('/api/contacts', $data);

        static::assertSame([
            'id' => 1001,
            'firstName' => 'foo bar',
            'lastName' => null,
            'client' => '/api/clients/1000',
            'email' => 'foo@bar.com',
            'additionalContactDetails' => [],
        ], $result);
    }

    public function testDelete()
    {
        $this->requestDelete('/api/contacts/1000');
    }

    public function testGet()
    {
        $data = $this->requestGet('/api/contacts/1000');

        static::assertSame([
            'id' => 1000,
            'firstName' => 'Test',
            'lastName' => null,
            'client' => '/api/clients/1000',
            'email' => 'test@example.com',
            'additionalContactDetails' => [],
        ], $data);
    }

    public function testEdit()
    {
        $data = $this->requestPut('/api/contacts/1000', ['firstName' => 'New Test']);

        static::assertSame([
            'id' => 1000,
            'firstName' => 'New Test',
            'lastName' => null,
            'client' => '/api/clients/1000',
            'email' => 'test@example.com',
            'additionalContactDetails' => [],
        ], $data);
    }
}
