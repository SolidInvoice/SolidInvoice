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

namespace SolidInvoice\PaymentBundle\Tests\Functional\Api;

use Liip\TestFixturesBundle\Test\FixturesTrait;
use SolidInvoice\ApiBundle\Test\ApiTestCase;
use SolidInvoice\ClientBundle\DataFixtures\ORM\LoadData;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;

/**
 * @group functional
 */
class PaymentTest extends ApiTestCase
{
    use EnsureApplicationInstalled;
    use FixturesTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadFixtures([
            LoadData::class,
            \SolidInvoice\PaymentBundle\DataFixtures\ORM\LoadData::class,
        ], true);
    }

    public function testGetAll(): void
    {
        $data = $this->requestGet('/api/payments');

        self::assertSame([
            [
                'method' => null,
                'status' => 'captured',
                'message' => null,
                'completed' => null,
            ],
        ], $data);
    }

    public function testGet(): void
    {
        $data = $this->requestGet('/api/payments/1');

        self::assertSame([
            'method' => null,
            'status' => 'captured',
            'message' => null,
            'completed' => null,
        ], $data);
    }
}
