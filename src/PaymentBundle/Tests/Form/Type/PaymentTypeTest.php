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

namespace SolidInvoice\PaymentBundle\Tests\Form\Type;

use Money\Currency;
use Money\Money;
use SolidInvoice\CoreBundle\Form\Type\ImageUploadType;
use SolidInvoice\CoreBundle\Form\Type\Select2Type;
use SolidInvoice\CoreBundle\Tests\FormTestCase;
use SolidInvoice\PaymentBundle\Form\Type\PaymentType;

class PaymentTypeTest extends FormTestCase
{
    public function testSubmit(): void
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

        $this->assertFormData($this->factory->create(PaymentType::class, [], ['currency' => new Currency('USD'), 'preferred_choices' => [], 'user' => null]), $formData, $object);
    }

    protected function getTypes(): array
    {
        $types = parent::getTypes();

        $types[] = new PaymentType($this->registry);

        return $types;
    }
}
