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

use Ramsey\Uuid\Uuid;
use SolidInvoice\ApiBundle\Test\ApiTestCase;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;

/**
 * @group functional
 */
final class ClientTest extends ApiTestCase
{
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
            'credit' => '0',
        ], $result);
    }

    public function testDelete(): void
    {
        $client = ClientFactory::createOne(['company' => $this->company, 'archived' => false])->object();

        $this->requestDelete('/api/clients/' . $client->getId());
    }

    public function testGet(): void
    {
        /** @var Client $client */
        $client = ClientFactory::createOne([
            'company' => $this->company,
            'archived' => false,
        ])->object();

        $data = $this->requestGet('/api/clients/' . $client->getId());

        self::assertSame([
            'id' => $client->getId()->toString(),
            'name' => $client->getName(),
            'website' => $client->getWebsite(),
            'status' => $client->getStatus(),
            'currency' => $client->getCurrency()->getCode(),
            'vatNumber' => $client->getVatNumber(),
            'contacts' => [
                /*[
                    'id' => $client->getContacts()->first()->getId()->toString(),
                    'firstName' => 'Test',
                    'lastName' => null,
                    'email' => 'test@example.com',
                    'additionalContactDetails' => [],
                ],*/
            ],
            'addresses' => [],
            'credit' => '0',
        ], $data);
    }

    public function testEdit(): void
    {
        /** @var Client $client */
        $client = ClientFactory::createOne([
            'company' => $this->company,
            'archived' => false,
            'contacts' => [
                (new Contact())
                    ->setFirstName('Test')
                    ->setEmail('test@example.com')
            ]
        ])->object();

        $data = $this->requestPut('/api/clients/' . $client->getId(), ['name' => 'New Test']);

        self::assertSame([
            'id' => $client->getId()->toString(),
            'name' => 'New Test',
            'website' => $client->getWebsite(),
            'status' => $client->getStatus(),
            'currency' => $client->getCurrency()->getCode(),
            'vatNumber' => $client->getVatNumber(),
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
            'credit' => '0',
        ], $data);
    }
}
