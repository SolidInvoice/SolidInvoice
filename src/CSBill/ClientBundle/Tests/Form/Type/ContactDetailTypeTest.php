<?php
/**
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\ClientBundle\Tests\Form\Type;

use CSBill\ClientBundle\Entity\ContactType;
use CSBill\ClientBundle\Form\Type\ContactDetailType;
use CSBill\CoreBundle\Tests\FormTestCase;
use Faker\Factory;
use Symfony\Component\Form\PreloadedExtension;

class ContactDetailTypeTest extends FormTestCase
{
    public function testSubmit()
    {
        $faker = Factory::create();

        $url = $faker->url;

        $contactType = new ContactType;
        $ref = new \ReflectionProperty($contactType, 'id');
        $ref->setAccessible(true);
        $ref->setValue($contactType, 1);

        $formData = [
            'value' => $url,
            'type' => 1,
        ];

        $contactType = new ContactType;
        $ref = new \ReflectionProperty($contactType, 'id');
        $ref->setAccessible(true);
        $ref->setValue($contactType, 1);

        $object = [
            'value' => $url,
            'type' => $contactType,
        ];

        $this->assertFormData(ContactDetailType::class, $formData, $object);
    }

    protected function getExtensions()
    {
        // create a type instance with the mocked dependencies
        $contactType = new ContactType;
        $ref = new \ReflectionProperty($contactType, 'id');
        $ref->setAccessible(true);
        $ref->setValue($contactType, 1);

        $type = new ContactDetailType([$contactType]);

        return [
            // register the type instances with the PreloadedExtension
            new PreloadedExtension([$type], []),
        ];
    }
}
