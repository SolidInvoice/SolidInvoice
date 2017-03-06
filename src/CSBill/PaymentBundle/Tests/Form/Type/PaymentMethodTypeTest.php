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
use CSBill\PaymentBundle\Form\Type\PaymentMethodType;

class PaymentMethodTypeTest extends FormTestCase
{
    public function testSubmit()
    {
        $name = $this->faker->name;
        $enabled = $this->faker->boolean;
        $internal = $this->faker->boolean;

        $formData = [
            'name' => $name,
            'enabled' => $enabled,
            'internal' => $internal,
        ];

        $object = [
            'name' => $name,
            'enabled' => $enabled,
            'internal' => $internal,
        ];

        $this->assertFormData($this->factory->create(PaymentMethodType::class, [], ['config' => null]), $formData, $object);
    }
}
