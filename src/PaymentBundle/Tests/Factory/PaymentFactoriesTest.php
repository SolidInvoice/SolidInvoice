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

    public function testSetGatewayFactories()
    {
        $data = [
            'cash' => 'offline',
            'credit' => 'offline',
            'paypal' => 'paypal_express',
        ];

        $paymentFactories = new PaymentFactories();

        $paymentFactories->setGatewayFactories($data);

        static::assertSame($data, $paymentFactories->getFactories());
    }

    public function testGetGatewayFactories()
    {
        $paymentFactories = new PaymentFactories();

        static::assertEmpty($paymentFactories->getFactories());
    }

    public function testGetSpecificGatewayFactories()
    {
        $data = [
            'cash' => 'offline',
            'credit' => 'offline',
            'paypal' => 'paypal_express',
        ];

        $paymentFactories = new PaymentFactories();
        $paymentFactories->setGatewayFactories($data);

        static::assertSame(['cash' => 'offline', 'credit' => 'offline'], $paymentFactories->getFactories('offline'));
        static::assertSame(['paypal' => 'paypal_express'], $paymentFactories->getFactories('paypal_express'));
        static::assertSame([], $paymentFactories->getFactories('paypal_pro'));
    }

    public function testIsOffline()
    {
        $data = [
            'cash' => 'offline',
            'credit' => 'offline',
            'paypal' => 'paypal_express',
        ];

        $paymentFactories = new PaymentFactories();
        $paymentFactories->setGatewayFactories($data);

        static::assertTrue($paymentFactories->isOffline('cash'));
        static::assertTrue($paymentFactories->isOffline('credit'));
        static::assertFalse($paymentFactories->isOffline('paypal'));
        static::assertFalse($paymentFactories->isOffline('payex'));
    }

    public function testGetFactory()
    {
        $data = [
            'cash' => 'offline',
            'credit' => 'offline',
            'paypal' => 'paypal_express',
        ];

        $paymentFactories = new PaymentFactories();
        $paymentFactories->setGatewayFactories($data);

        static::assertSame('offline', $paymentFactories->getFactory('cash'));
        static::assertSame('offline', $paymentFactories->getFactory('credit'));
        static::assertSame('paypal_express', $paymentFactories->getFactory('paypal'));
    }

    public function testGetEmptyFactory()
    {
        $paymentFactories = new PaymentFactories();

        $this->expectException(InvalidGatewayException::class);
        $this->expectExceptionMessage('Invalid gateway: unknown');
        $paymentFactories->getFactory('unknown');
    }

    public function testSetGatewayForms()
    {
        $paymentFactories = new PaymentFactories();

        $data = [
            'cash' => 'cash_form',
            'credit' => 'credit_form',
            'paypal' => 'paypal_form',
        ];

        $paymentFactories->setGatewayForms($data);

        static::assertSame('cash_form', $paymentFactories->getForm('cash'));
        static::assertSame('credit_form', $paymentFactories->getForm('credit'));
        static::assertSame('paypal_form', $paymentFactories->getForm('paypal'));
        static::assertNull($paymentFactories->getForm('payex'));
    }
}
