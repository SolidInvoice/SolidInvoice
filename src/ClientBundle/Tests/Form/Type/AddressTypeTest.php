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

namespace SolidInvoice\ClientBundle\Tests\Form\Type;

use Faker\Factory;
use SolidInvoice\ClientBundle\Entity\Address;
use SolidInvoice\ClientBundle\Form\Type\AddressType;
use SolidInvoice\CoreBundle\Tests\FormTestCase;

class AddressTypeTest extends FormTestCase
{
    public function testSubmit(): void
    {
        $faker = Factory::create();

        $street1 = $faker->buildingNumber . ' ' . $faker->streetName;
        $street2 = $faker->randomNumber(2) . ' ' . $faker->streetName . ' ' . $faker->streetSuffix;
        $city = $faker->city;
        $postcode = $faker->postcode;
        $countryCode = $faker->countryCode;

        $formData = [
            'street1' => $street1,
            'street2' => $street2,
            'city' => $city,
            'state' => $city,
            'zip' => $postcode,
            'country' => $countryCode,
        ];

        $entity = (new Address())
            ->setStreet1($street1)
            ->setStreet2($street2)
            ->setCity($city)
            ->setState($city)
            ->setZip($postcode)
            ->setCountry($countryCode);

        $this->assertFormData(AddressType::class, $formData, $entity);
    }
}
