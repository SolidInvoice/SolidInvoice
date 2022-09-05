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

namespace SolidInvoice\PaymentBundle\Tests\Factory;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use SolidInvoice\PaymentBundle\Exception\InvalidGatewayException;
use SolidInvoice\PaymentBundle\Factory\PaymentFactories;

class PaymentFactoriesTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testSetGatewayFactories(): void
    {
        $data = [
            'cash' => 'offline',
            'credit' => 'offline',
            'paypal' => 'paypal_express',
        ];

        $paymentFactories = new PaymentFactories();

        $paymentFactories->setGatewayFactories($data);

        self::assertSame($data, $paymentFactories->getFactories());
    }

    public function testGetGatewayFactories(): void
    {
        $paymentFactories = new PaymentFactories();

        self::assertEmpty($paymentFactories->getFactories());
    }

    public function testGetSpecificGatewayFactories(): void
    {
        $data = [
            'cash' => 'offline',
            'credit' => 'offline',
            'paypal' => 'paypal_express',
        ];

        $paymentFactories = new PaymentFactories();
        $paymentFactories->setGatewayFactories($data);

        self::assertSame(['cash' => 'offline', 'credit' => 'offline'], $paymentFactories->getFactories('offline'));
        self::assertSame(['paypal' => 'paypal_express'], $paymentFactories->getFactories('paypal_express'));
        self::assertSame([], $paymentFactories->getFactories('paypal_pro'));
    }

    public function testIsOffline(): void
    {
        $data = [
            'cash' => 'offline',
            'credit' => 'offline',
            'paypal' => 'paypal_express',
        ];

        $paymentFactories = new PaymentFactories();
        $paymentFactories->setGatewayFactories($data);

        self::assertTrue($paymentFactories->isOffline('cash'));
        self::assertTrue($paymentFactories->isOffline('credit'));
        self::assertFalse($paymentFactories->isOffline('paypal'));
        self::assertFalse($paymentFactories->isOffline('payex'));
    }

    public function testGetFactory(): void
    {
        $data = [
            'cash' => 'offline',
            'credit' => 'offline',
            'paypal' => 'paypal_express',
        ];

        $paymentFactories = new PaymentFactories();
        $paymentFactories->setGatewayFactories($data);

        self::assertSame('offline', $paymentFactories->getFactory('cash'));
        self::assertSame('offline', $paymentFactories->getFactory('credit'));
        self::assertSame('paypal_express', $paymentFactories->getFactory('paypal'));
    }

    public function testGetEmptyFactory(): void
    {
        $paymentFactories = new PaymentFactories();

        $this->expectException(InvalidGatewayException::class);
        $this->expectExceptionMessage('Invalid gateway: unknown');
        $paymentFactories->getFactory('unknown');
    }

    public function testSetGatewayForms(): void
    {
        $paymentFactories = new PaymentFactories();

        $data = [
            'cash' => 'cash_form',
            'credit' => 'credit_form',
            'paypal' => 'paypal_form',
        ];

        $paymentFactories->setGatewayForms($data);

        self::assertSame('cash_form', $paymentFactories->getForm('cash'));
        self::assertSame('credit_form', $paymentFactories->getForm('credit'));
        self::assertSame('paypal_form', $paymentFactories->getForm('paypal'));
        self::assertNull($paymentFactories->getForm('payex'));
    }
}
