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

use Doctrine\Common\DataFixtures\Executor\AbstractExecutor;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Ramsey\Uuid\Uuid;
use SolidInvoice\ApiBundle\Test\ApiTestCase;
use SolidInvoice\ClientBundle\DataFixtures\ORM\LoadData;
use SolidInvoice\ClientBundle\Entity\Client;
use function assert;

/**
 * @group functional
 */
final class ClientTest extends ApiTestCase
{
    private AbstractExecutor $executor;

    protected function setUp(): void
    {
        parent::setUp();

        $databaseTool = self::getContainer()->get(DatabaseToolCollection::class)->get();

        $this->executor = $databaseTool->loadFixtures([
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

        self::assertArrayHasKey('id', $result);
        self::assertArrayHasKey('id', $result['contacts'][0]);
        self::assertTrue(Uuid::isValid($result['id']));
        self::assertTrue(Uuid::isValid($result['contacts'][0]['id']));

        unset($result['id'], $result['contacts'][0]['id']);

        self::assertSame([
            'name' => 'Dummy User',
            'website' => null,
            'status' => 'active',
            'currency' => 'USD',
            'vatNumber' => null,
            'contacts' => [
                [
                    'firstName' => 'foo bar',
                    'lastName' => null,
                    'email' => 'foo@example.com',
                    'additionalContactDetails' => [],
                ],
            ],
            'addresses' => [],
            'credit' => 0,
        ], $result);
    }

    public function testDelete(): void
    {
        $client = $this->executor->getReferenceRepository()->getReference('client');
        assert($client instanceof Client);

        $this->requestDelete('/api/clients/' . $client->getId());
    }

    public function testGet(): void
    {
        $client = $this->executor->getReferenceRepository()->getReference('client');
        assert($client instanceof Client);

        $data = $this->requestGet('/api/clients/' . $client->getId());

        self::assertSame([
            'id' => $client->getId()->toString(),
            'name' => 'Test',
            'website' => null,
            'status' => 'active',
            'currency' => 'USD',
            'vatNumber' => null,
            'contacts' => [
                [
                    'id' => $client->getContacts()->first()->getId()->toString(),
                    'firstName' => 'Test',
                    'lastName' => null,
                    'email' => 'test@example.com',
                    'additionalContactDetails' => [],
                ],
            ],
            'addresses' => [],
            'credit' => 0,
        ], $data);
    }

    public function testEdit(): void
    {
        $client = $this->executor->getReferenceRepository()->getReference('client');
        assert($client instanceof Client);

        $data = $this->requestPut('/api/clients/' . $client->getId(), ['name' => 'New Test']);

        self::assertSame([
            'id' => $client->getId()->toString(),
            'name' => 'New Test',
            'website' => null,
            'status' => 'active',
            'currency' => 'USD',
            'vatNumber' => null,
            'contacts' => [
                [
                    'id' => $client->getContacts()->first()->getId()->toString(),
                    'firstName' => 'Test',
                    'lastName' => null,
                    'email' => 'test@example.com',
                    'additionalContactDetails' => [],
                ],
            ],
            'addresses' => [],
            'credit' => 0,
        ], $data);
    }
}
