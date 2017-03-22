<?php

declare(strict_types=1);
/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\PaymentBundle\Tests\Factory;

use CSBill\PaymentBundle\Exception\InvalidGatewayException;
use CSBill\PaymentBundle\Factory\PaymentFactories;

class PaymentFactoriesTest extends \PHPUnit\Framework\TestCase
{
    public function testSetGatewayFactories()
    {
        $data = [
            'cash' => 'offline',
            'credit' => 'offline',
            'paypal' => 'paypal_express',
        ];

        $paymentFactories = new PaymentFactories();

        $paymentFactories->setGatewayFactories($data);

        $this->assertSame($data, $paymentFactories->getFactories());
    }

    public function testGetGatewayFactories()
    {
        $paymentFactories = new PaymentFactories();

        $this->assertEmpty($paymentFactories->getFactories());
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

        $this->assertSame(['cash' => 'offline', 'credit' => 'offline'], $paymentFactories->getFactories('offline'));
        $this->assertSame(['paypal' => 'paypal_express'], $paymentFactories->getFactories('paypal_express'));
        $this->assertSame([], $paymentFactories->getFactories('paypal_pro'));
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

        $this->assertTrue($paymentFactories->isOffline('cash'));
        $this->assertTrue($paymentFactories->isOffline('credit'));
        $this->assertFalse($paymentFactories->isOffline('paypal'));
        $this->assertFalse($paymentFactories->isOffline('payex'));
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

        $this->assertSame('offline', $paymentFactories->getFactory('cash'));
        $this->assertSame('offline', $paymentFactories->getFactory('credit'));
        $this->assertSame('paypal_express', $paymentFactories->getFactory('paypal'));
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

        $this->assertSame('cash_form', $paymentFactories->getForm('cash'));
        $this->assertSame('credit_form', $paymentFactories->getForm('credit'));
        $this->assertSame('paypal_form', $paymentFactories->getForm('paypal'));
        $this->assertNull($paymentFactories->getForm('payex'));
    }
}
