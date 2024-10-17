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
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\ClientBundle\Test\Factory\ContactFactory;
use Zenstruck\Foundry\Test\Factories;

/**
 * @group functional
 */
final class ContactTest extends ApiTestCase
{
    use Factories;

    protected function getResourceClass(): string
    {
        return Contact::class;
    }

    public function testCreate(): void
    {
        $client = ClientFactory::createOne()->object();

        $data = [
            'client' => $this->getIriFromResource($client),
            'firstName' => 'foo bar',
            'email' => 'foo@bar.com',
        ];

        $result = $this->requestPost($this->getIriFromResource($client) . '/contacts', $data);

        self::assertTrue(Uuid::isValid($result['id']));

        self::assertEqualsCanonicalizing([
            '@context' => '/api/contexts/Contact',
            '@id' => $this->getIriFromResource($client) . '/contacts/' . $result['id'],
            '@type' => 'Contact',
            'id' => $result['id'],
            'firstName' => 'foo bar',
            'lastName' => null,
            'client' => $this->getIriFromResource($client),
            'email' => 'foo@bar.com',
        ], $result);
    }

    public function testDelete(): void
    {
        $client = ClientFactory::createOne()->object();
        $contact = ContactFactory::createOne(['client' => $client])->object();

        $this->requestDelete($this->getIriFromResource($contact));
    }

    public function testGet(): void
    {
        $firstName = $this->faker->firstName();
        $lastName = $this->faker->firstName();
        $email = $this->faker->email();

        $client = ClientFactory::createOne()->object();
        $contact = ContactFactory::createOne([
            'firstName' => $firstName,
            'lastName' => $lastName,
            'email' => $email,
            'client' => $client,
        ])->object();

        $data = $this->requestGet($this->getIriFromResource($contact));

        self::assertEqualsCanonicalizing([
            '@context' => '/api/contexts/Contact',
            '@id' => $this->getIriFromResource($contact),
            '@type' => 'Contact',
            'id' => $contact->getId()->toString(),
            'firstName' => $firstName,
            'lastName' => $lastName,
            'client' => $this->getIriFromResource($client),
            'email' => $email,
        ], $data);
    }

    public function testEdit(): void
    {
        $client = ClientFactory::createOne()->object();
        $contact = ContactFactory::createOne([
            'lastName' => null,
            'email' => 'test@example.com',
            'client' => $client,
        ])->object();

        $data = $this->requestPatch($this->getIriFromResource($contact), ['firstName' => 'New Test']);

        self::assertEqualsCanonicalizing([
            '@context' => '/api/contexts/Contact',
            '@id' => $this->getIriFromResource($contact),
            '@type' => 'Contact',
            'id' => $contact->getId()->toString(),
            'firstName' => 'New Test',
            'lastName' => null,
            'client' => $this->getIriFromResource($client),
            'email' => 'test@example.com',
        ], $data);
    }
}
