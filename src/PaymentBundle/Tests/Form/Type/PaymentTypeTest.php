<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\PaymentBundle\Tests\Form\Type;

use SolidInvoice\CoreBundle\Tests\FormTestCase;
use SolidInvoice\PaymentBundle\Entity\PaymentMethod;
use SolidInvoice\PaymentBundle\Form\Type\PaymentType;
use Money\Currency;
use Money\Money;

class PaymentTypeTest extends FormTestCase
{
    public function testSubmit()
    {
        $paymentMethod = $this->faker->name;
        $amount = $this->faker->randomNumber();

        $formData = [
            'payment_method' => $paymentMethod,
            'amount' => $amount,
        ];

        $object = [
            'amount' => new Money($amount * 100, new Currency('USD')),
        ];

        $this->assertFormData($this->factory->create(PaymentType::class, [], ['currency' => 'USD', 'preferred_choices' => [], 'user' => null]), $formData, $object);
    }

    protected function getEntityNamespaces()
    {
        return [
            'SolidInvoicePaymentBundle' => 'SolidInvoice\PaymentBundle\Entity',
        ];
    }

    protected function getEntities()
    {
        return [
            PaymentMethod::class,
        ];
    }
}
