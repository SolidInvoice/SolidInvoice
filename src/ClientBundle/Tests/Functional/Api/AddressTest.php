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
use SolidInvoice\ApiBundle\Test\ApiTestCase;
use SolidInvoice\ClientBundle\Entity\Address;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use Symfony\Component\Uid\Ulid;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Zenstruck\Foundry\Test\Factories;

/**
 * @group functional
 */
final class AddressTest extends ApiTestCase
{
    use Factories;

    protected function getResourceClass(): string
    {
        return Address::class;
    }

    public function testCreate(): void
    {
        $data = [
            'street1' => 'foo',
            'street2' => 'foo',
            'city' => 'foo',
            'state' => 'foo',
            'zip' => 'foo',
            'country' => 'US',
        ];

        $client = ClientFactory::createOne()->_real();

        $result = $this->requestPost($this->getIriFromResource($client) . '/addresses', $data);

        self::assertArrayHasKey('id', $result);
        self::assertTrue(Ulid::isValid($result['id']));
        unset($result['id'], $result['@id']);

        self::assertEqualsCanonicalizing([
            '@context' => $this->getContextForResource($this->getResourceClass()),
            '@type' => 'Address',
            'street1' => 'foo',
            'street2' => 'foo',
            'city' => 'foo',
            'state' => 'foo',
            'zip' => 'foo',
            'country' => 'US',
            'countryName' => 'United States',
            'client' => $this->getIriFromResource($client),
        ], $result);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function testDelete(): void
    {
        ClientFactory::createOne([
            'addresses' => [
                $address = new Address(),
            ],
        ])->_real();

        $this->requestDelete($this->getIriFromResource($address));
    }

    /**
     * @throws TransportExceptionInterface
     * @throws JsonException
     */
    public function testGet(): void
    {
        $client = ClientFactory::createOne([
            'addresses' => [
                $address = (new Address())
                    ->setStreet1('street 1')
                    ->setStreet2('street 2')
                    ->setCity('city')
                    ->setState('state')
                    ->setCountry('US')
                    ->setZip('1234'),
            ],
        ])->_real();

        $data = $this->requestGet($this->getIriFromResource($address));

        self::assertEqualsCanonicalizing([
            '@context' => $this->getContextForResource($this->getResourceClass()),
            '@id' => $this->getIriFromResource($address),
            '@type' => 'Address',
            'id' => $address->getId()->toString(),
            'street1' => 'street 1',
            'street2' => 'street 2',
            'city' => 'city',
            'state' => 'state',
            'zip' => '1234',
            'country' => 'US',
            'countryName' => 'United States',
            'client' => $this->getIriFromResource($client),
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
        $client = ClientFactory::createOne([
            'addresses' => [
                $address = (new Address())
                    ->setStreet1('street 1')
                    ->setStreet2('street 2')
                    ->setCity('city')
                    ->setState('state')
                    ->setCountry('US')
                    ->setZip('1234'),
            ],
        ])->_real();

        $data = $this->requestPatch(
            $this->getIriFromResource($address),
            [
                'street2' => 'street1',
                'street1' => 'street2',
                'country' => 'ZA',
            ]
        );

        self::assertEqualsCanonicalizing([
            '@context' => $this->getContextForResource($this->getResourceClass()),
            '@id' => $this->getIriFromResource($address),
            '@type' => 'Address',
            'id' => $address->getId()->toString(),
            'street1' => 'street2',
            'street2' => 'street1',
            'city' => 'city',
            'state' => 'state',
            'zip' => '1234',
            'country' => 'ZA',
            'countryName' => 'South Africa',
            'client' => $this->getIriFromResource($client),
        ], $data);
    }
}
