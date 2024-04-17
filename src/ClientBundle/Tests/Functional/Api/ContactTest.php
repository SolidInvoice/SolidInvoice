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
use SolidInvoice\ClientBundle\Entity\Contact;
use function assert;

/**
 * @group functional
 */
final class ContactTest extends ApiTestCase
{
    private AbstractExecutor $executor;

    protected function setUp(): void
    {
        parent::setUp();

        $databaseTool = static::getContainer()->get(DatabaseToolCollection::class)->get();

        $this->executor = $databaseTool->loadFixtures([
            LoadData::class,
        ], true);
    }

    public function testCreate(): void
    {
        $client = $this->executor->getReferenceRepository()->getReference('client');
        assert($client instanceof Client);

        $data = [
            'client' => '/api/clients/' . $client->getId(),
            'firstName' => 'foo bar',
            'email' => 'foo@bar.com',
        ];

        $result = $this->requestPost('/api/contacts', $data);

        self::assertTrue(Uuid::isValid($result['id']));

        unset($result['id']);

        self::assertSame([
            'firstName' => 'foo bar',
            'lastName' => null,
            'client' => '/api/clients/' . $client->getId(),
            'email' => 'foo@bar.com',
            'additionalContactDetails' => [],
        ], $result);
    }

    public function testDelete(): void
    {
        $contact = $this->executor->getReferenceRepository()->getReference('contact');
        assert($contact instanceof Contact);

        $this->requestDelete('/api/contacts/' . $contact->getId());
    }

    public function testGet(): void
    {
        $contact = $this->executor->getReferenceRepository()->getReference('contact');
        assert($contact instanceof Contact);

        $data = $this->requestGet('/api/contacts/' . $contact->getId());

        self::assertSame([
            'id' => $contact->getId()->toString(),
            'firstName' => 'Test',
            'lastName' => null,
            'client' => '/api/clients/' . $contact->getClient()->getId()->toString(),
            'email' => 'test@example.com',
            'additionalContactDetails' => [],
        ], $data);
    }

    public function testEdit(): void
    {
        $contact = $this->executor->getReferenceRepository()->getReference('contact');
        assert($contact instanceof Contact);

        $data = $this->requestPut('/api/contacts/' . $contact->getId(), ['firstName' => 'New Test']);

        self::assertSame([
            'id' => $contact->getId()->toString(),
            'firstName' => 'New Test',
            'lastName' => null,
            'client' => '/api/clients/' . $contact->getClient()->getId()->toString(),
            'email' => 'test@example.com',
            'additionalContactDetails' => [],
        ], $data);
    }
}
