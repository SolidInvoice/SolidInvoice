<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\TaxBundle\Tests\Form\Type;

use CSBill\CoreBundle\Tests\FormTestCase;
use CSBill\TaxBundle\Entity\Tax;
use CSBill\TaxBundle\Form\Type\TaxType;

class TaxTypeTest extends FormTestCase
{
    public function testSubmit()
    {
        $name = $this->faker->name;
        $rate = $this->faker->randomFloat(2, 0, 100);
        $type = ucwords($this->faker->randomKey(Tax::getTypes()));

        $formData = [
            'name' => $name,
            'rate' => $rate,
            'type' => $type,
        ];

        $object = new Tax();
        $object->setName($name);
        $object->setRate($rate);
        $object->setType($type);

        $this->assertFormData(TaxType::class, $formData, $object);
    }
}
