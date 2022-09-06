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

use ReflectionProperty;
use SolidInvoice\ClientBundle\Entity;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\ClientBundle\Form\Type\ContactDetailType;
use SolidInvoice\ClientBundle\Form\Type\ContactType;
use SolidInvoice\CoreBundle\Tests\FormTestCase;
use Symfony\Component\Form\PreloadedExtension;

class ContactTypeTest extends FormTestCase
{
    public function testSubmit(): void
    {
        $firstName = $this->faker->firstName;
        $lastName = $this->faker->lastName;
        $email = $this->faker->email;

        $formData = [
            'firstName' => $firstName,
            'lastName' => $lastName,
            'email' => $email,
        ];

        $object = new Contact();
        $object->setFirstName($firstName);
        $object->setLastName($lastName);
        $object->setEmail($email);

        $this->assertFormData(ContactType::class, $formData, $object);
    }

    protected function getExtensions()
    {
        // create a type instance with the mocked dependencies
        $contactType = new Entity\ContactType();
        $ref = new ReflectionProperty($contactType, 'id');
        $ref->setAccessible(true);
        $ref->setValue($contactType, 1);

        $type = new ContactDetailType($this->registry->getRepository(Entity\ContactType::class));

        return [
            // register the type instances with the PreloadedExtension
            new PreloadedExtension([$type], []),
        ];
    }
}
