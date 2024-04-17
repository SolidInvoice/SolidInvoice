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

use Doctrine\Common\DataFixtures\Executor\AbstractExecutor;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use SolidInvoice\ApiBundle\Test\ApiTestCase;
use SolidInvoice\ClientBundle\DataFixtures\ORM\LoadData as LoadClientData;
use SolidInvoice\PaymentBundle\DataFixtures\ORM\LoadData as LoadPaymentData;
use SolidInvoice\PaymentBundle\Entity\Payment;
use function assert;

/**
 * @group functional
 */
final class PaymentTest extends ApiTestCase
{
    private AbstractExecutor $executor;

    protected function setUp(): void
    {
        parent::setUp();

        $databaseTool = static::getContainer()->get(DatabaseToolCollection::class)->get();

        $this->executor = $databaseTool->loadFixtures([
            LoadClientData::class,
            LoadPaymentData::class,
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
        $payment = $this->executor->getReferenceRepository()->getReference('payment');
        assert($payment instanceof Payment);

        $data = $this->requestGet('/api/payments/' . $payment->getId());

        self::assertSame([
            'method' => null,
            'status' => 'captured',
            'message' => null,
            'completed' => null,
        ], $data);
    }
}
