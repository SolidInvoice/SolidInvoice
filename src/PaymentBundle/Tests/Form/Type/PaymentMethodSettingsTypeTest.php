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

use SolidInvoice\CoreBundle\Tests\FormTestCase;
use SolidInvoice\PaymentBundle\Form\Type\PaymentMethodSettingsType;

class PaymentMethodSettingsTypeTest extends FormTestCase
{
    public function testSubmit()
    {
        $paragraphs = $this->faker->paragraphs;

        $one = $this->faker->name;
        $two = $this->faker->boolean;
        $three = $this->faker->password;
        $four = $this->faker->randomKey($paragraphs);

        $formData = [
            'one' => $one,
            'two' => $two,
            'three' => $three,
            'four' => $four,
        ];

        $object = [
            'one' => $one,
            'two' => $two,
            'three' => $three,
            'four' => $four,
        ];

        $options = [
            'settings' => [
                [
                    'name' => 'one',
                    'type' => 'text',
                ],
                [
                    'name' => 'two',
                    'type' => 'checkbox',
                ],
                [
                    'name' => 'three',
                    'type' => 'password',
                ],
                [
                    'name' => 'four',
                    'type' => 'choice',
                    'options' => $paragraphs,
                ],
            ],
        ];

        $this->assertFormData($this->factory->create(PaymentMethodSettingsType::class, [], $options), $formData, $object);
    }
}
