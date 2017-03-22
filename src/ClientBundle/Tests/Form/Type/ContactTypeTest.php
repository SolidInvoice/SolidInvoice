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

use CSBill\ClientBundle\Entity;
use CSBill\ClientBundle\Form\Type\ContactDetailType;
use CSBill\ClientBundle\Form\Type\ContactType;
use CSBill\CoreBundle\Tests\FormTestCase;
use Symfony\Component\Form\PreloadedExtension;

class ContactTypeTest extends FormTestCase
{
    public function testSubmit()
    {
        $firstName = $this->faker->firstName;
        $lastName = $this->faker->lastName;
        $email = $this->faker->email;

        $formData = [
            'firstName' => $firstName,
            'lastName' => $lastName,
            'email' => $email,
        ];

        $object = new Entity\Contact();
        $object->setFirstName($firstName);
        $object->setLastName($lastName);
        $object->setEmail($email);

        $this->assertFormData(ContactType::class, $formData, $object);
    }

    protected function getExtensions()
    {
        // create a type instance with the mocked dependencies
        $contactType = new Entity\ContactType();
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
