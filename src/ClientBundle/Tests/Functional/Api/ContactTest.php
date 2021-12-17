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

use Liip\TestFixturesBundle\Test\FixturesTrait;
use SolidInvoice\ApiBundle\Test\ApiTestCase;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;

/**
 * @group functional
 */
class ContactTest extends ApiTestCase
{
    use FixturesTrait;
    use EnsureApplicationInstalled;

    public function setUp(): void
    {
        parent::setUp();

        $this->loadFixtures([
            'SolidInvoice\ClientBundle\DataFixtures\ORM\LoadData',
        ], true);
    }

    public function testCreate()
    {
        $data = [
            'client' => '/api/clients/1',
            'firstName' => 'foo bar',
            'email' => 'foo@bar.com',
        ];

        $result = $this->requestPost('/api/contacts', $data);

        static::assertSame([
            'id' => 2,
            'firstName' => 'foo bar',
            'lastName' => null,
            'client' => '/api/clients/1',
            'email' => 'foo@bar.com',
            'additionalContactDetails' => [],
        ], $result);
    }

    public function testDelete()
    {
        $this->requestDelete('/api/contacts/1');
    }

    public function testGet()
    {
        $data = $this->requestGet('/api/contacts/1');

        static::assertSame([
            'id' => 1,
            'firstName' => 'Test',
            'lastName' => null,
            'client' => '/api/clients/1',
            'email' => 'test@example.com',
            'additionalContactDetails' => [],
        ], $data);
    }

    public function testEdit()
    {
        $data = $this->requestPut('/api/contacts/1', ['firstName' => 'New Test']);

        static::assertSame([
            'id' => 1,
            'firstName' => 'New Test',
            'lastName' => null,
            'client' => '/api/clients/1',
            'email' => 'test@example.com',
            'additionalContactDetails' => [],
        ], $data);
    }
}
