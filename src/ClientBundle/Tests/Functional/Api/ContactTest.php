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
class ContactTest extends ApiTestCase
{
    use EnsureApplicationInstalled;

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
            'client' => '/api/clients/1',
            'firstName' => 'foo bar',
            'email' => 'foo@bar.com',
        ];

        $result = $this->requestPost('/api/contacts', $data);

        self::assertSame([
            'id' => 2,
            'firstName' => 'foo bar',
            'lastName' => null,
            'client' => '/api/clients/1',
            'email' => 'foo@bar.com',
            'additionalContactDetails' => [],
        ], $result);
    }

    public function testDelete(): void
    {
        $this->requestDelete('/api/contacts/1');
    }

    public function testGet(): void
    {
        $data = $this->requestGet('/api/contacts/1');

        self::assertSame([
            'id' => 1,
            'firstName' => 'Test',
            'lastName' => null,
            'client' => '/api/clients/1',
            'email' => 'test@example.com',
            'additionalContactDetails' => [],
        ], $data);
    }

    public function testEdit(): void
    {
        $data = $this->requestPut('/api/contacts/1', ['firstName' => 'New Test']);

        self::assertSame([
            'id' => 1,
            'firstName' => 'New Test',
            'lastName' => null,
            'client' => '/api/clients/1',
            'email' => 'test@example.com',
            'additionalContactDetails' => [],
        ], $data);
    }
}
