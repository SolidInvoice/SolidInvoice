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

namespace CSBill\ClientBundle\Tests\Form\Type;

use CSBill\ClientBundle\Entity\Address;
use CSBill\ClientBundle\Form\Type\AddressType;
use CSBill\CoreBundle\Tests\FormTestCase;
use Faker\Factory;

class AddressTypeTest extends FormTestCase
{
    public function testSubmit()
    {
        $faker = Factory::create();

        $formData = [
            'street1' => $faker->buildingNumber.' '.$faker->streetName,
            'street2' => $faker->randomNumber(2).' '.$faker->streetName.' '.$faker->streetSuffix,
            'city' => $faker->city,
            'state' => $faker->state,
            'zip' => $faker->postcode,
            'country' => $faker->countryCode,
        ];

        $this->assertFormData(AddressType::class, $formData, $formData);
    }
}
