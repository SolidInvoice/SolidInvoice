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

use JsonException;
use Ramsey\Uuid\Uuid;
use SolidInvoice\ApiBundle\Test\ApiTestCase;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\ClientBundle\Test\Factory\ContactFactory;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\Test\Factories;
use function array_map;
use function assert;

/**
 * @group functional
 */
final class ClientTest extends ApiTestCase
{
    use Factories;

    protected function getResourceClass(): string
    {
        return Client::class;
    }

    public function testCreate(): void
    {
        $data = [
            'name' => 'Dummy User',
            'contacts' => [],
            'credit' => '125.50',
        ];

        $result = $this->requestPost('/api/clients', $data);

        self::assertArrayHasKey('id', $result);
        self::assertTrue(Uuid::isValid($result['id']));

        self::assertJsonContains([
            '@context' => $this->getContextForResource($this->getResourceClass()),
            '@type' => 'https://schema.org/Corporation',
            'name' => 'Dummy User',
            'website' => null,
            'status' => 'active',
            'currency' => 'USD',
            'vatNumber' => null,
            'contacts' => [],
            'addresses' => [],
            'credit' => 125.5,
        ]);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function testDelete(): void
    {
        $client = ClientFactory::createOne(['archived' => null, 'company' => $this->company])->object();

        $this->requestDelete($this->getIriFromResource($client));
    }

    /**
     * @throws TransportExceptionInterface
     * @throws JsonException
     */
    public function testGet(): void
    {
        $client = ClientFactory::createOne(['archived' => null, 'company' => $this->company])->object();
        assert($client instanceof Client);

        $contacts = ContactFactory::new(function () use ($client) {
            return [
                'client' => $client,
                'company' => $this->company,
            ];
        })->many(1, 5)->create();

        $data = $this->requestGet($this->getIriFromResource($client));

        self::assertEqualsCanonicalizing([
            '@context' => $this->getContextForResource($client),
            '@id' => $this->getIriFromResource($client),
            '@type' => 'https://schema.org/Corporation',
            'id' => $client->getId()->toString(),
            'name' => $client->getName(),
            'website' => $client->getWebsite(),
            'status' => $client->getStatus(),
            'currency' => $client->getCurrencyCode(),
            'vatNumber' => $client->getVatNumber(),
            'contacts' => array_map($this->getIriFromResource(...), array_map(static fn (Proxy $proxy) => $proxy->object(), $contacts)),
            'addresses' => [],
            'credit' => 0,
        ], $data);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function testEdit(): void
    {
        $client = ClientFactory::createOne(['archived' => null, 'company' => $this->company])->object();
        assert($client instanceof Client);

        $contacts = ContactFactory::new(function () use ($client) {
            return [
                'client' => $client,
                'company' => $this->company,
            ];
        })->many(4, 15)->create();

        $contactInfo = array_map(fn (Proxy $proxy): string => $this->getIriFromResource($proxy->object()), $contacts);

        $data = $this->requestPatch(
            $this->getIriFromResource($client),
            [
                'name' => 'New Test',
                'contacts' => $contactInfo,
            ]
        );

        self::assertEqualsCanonicalizing([
            '@context' => $this->getContextForResource($client),
            '@id' => $this->getIriFromResource($client),
            '@type' => 'https://schema.org/Corporation',
            'id' => $client->getId()->toString(),
            'name' => 'New Test',
            'website' => $client->getWebsite(),
            'status' => $client->getStatus(),
            'currency' => $client->getCurrencyCode(),
            'vatNumber' => $client->getVatNumber(),
            'contacts' => $contactInfo,
            'addresses' => [],
            'credit' => 0,
        ], $data);
    }
}
