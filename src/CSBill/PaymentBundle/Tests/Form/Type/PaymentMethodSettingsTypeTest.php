<?php
/**
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\PaymentBundle\Tests\Form\Type;

use CSBill\CoreBundle\Tests\FormTestCase;
use CSBill\PaymentBundle\Form\Type\PaymentMethodSettingsType;

class PaymentMethodSettingsTypeTest extends FormTestCase
{
    public function testSubmit()
    {
        $rgbColors = $this->faker->rgbColorAsArray;

        $one = $this->faker->name;
        $two = $this->faker->boolean;
        $three = $this->faker->password;
        $four = $this->faker->randomKey($rgbColors);

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
                    'options' => $rgbColors,
                ],
            ],
        ];

        $this->assertFormData($this->factory->create(PaymentMethodSettingsType::class, [], $options), $formData, $object);
    }
}
