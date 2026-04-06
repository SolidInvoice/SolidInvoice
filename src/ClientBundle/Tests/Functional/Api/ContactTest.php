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

use SolidInvoice\ApiBundle\Test\ApiTestCase;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\ClientBundle\Test\Factory\ContactFactory;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use Symfony\Component\Uid\Ulid;
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

    public function testGetCollection(): void
    {
        $client = ClientFactory::createOne()->_real();
        ContactFactory::createMany(3, ['client' => $client]);

        $data = $this->requestGetCollection($this->getIriFromResource($client) . '/contacts');

        self::assertArraySubset([
            '@context' => '/api/contexts/Contact',
            '@type' => 'Collection',
        ], $data);
    }

    public function testCannotCreateContactForClientFromDifferentCompany(): void
    {
        $otherCompany = CompanyFactory::new()->create();
        self::getContainer()->get(CompanySelector::class)->switchCompany($otherCompany->getId());
        $foreignClient = ClientFactory::createOne(['company' => $otherCompany])->_real();
        self::getContainer()->get(CompanySelector::class)->switchCompany($this->company->getId());

        // ContactPersistProcessor uses findOneBy() which applies the CompanyFilter,
        // so a foreign client is not found and a 404 is returned.
        self::$client->request('POST', $this->getIriFromResource($foreignClient) . '/contacts', [
            'json' => ['firstName' => 'Hacker', 'email' => 'x@y.com'],
            'headers' => ['content-type' => 'application/ld+json', 'accept' => 'application/ld+json'],
        ]);
        static::assertResponseStatusCodeSame(404);
    }

    public function testCreate(): void
    {
        $client = ClientFactory::createOne()->_real();

        $data = [
            'client' => $this->getIriFromResource($client),
            'firstName' => 'foo bar',
            'email' => 'foo@bar.com',
        ];

        $result = $this->requestPost($this->getIriFromResource($client) . '/contacts', $data);

        self::assertTrue(Ulid::isValid($result['id']));

        self::assertEqualsCanonicalizing([
            '@context' => '/api/contexts/Contact',
            '@id' => $this->getIriFromResource($client) . '/contact/' . $result['id'],
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
        $client = ClientFactory::createOne()->_real();
        $contact = ContactFactory::createOne(['client' => $client])->_real();

        $this->requestDelete($this->getIriFromResource($contact));
    }

    public function testGet(): void
    {
        $firstName = $this->faker->firstName();
        $lastName = $this->faker->firstName();
        $email = $this->faker->email();

        $client = ClientFactory::createOne()->_real();
        $contact = ContactFactory::createOne([
            'firstName' => $firstName,
            'lastName' => $lastName,
            'email' => $email,
            'client' => $client,
        ])->_real();

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
        $client = ClientFactory::createOne()->_real();
        $contact = ContactFactory::createOne([
            'lastName' => null,
            'email' => 'test@example.com',
            'client' => $client,
        ])->_real();

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
