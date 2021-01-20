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

namespace SolidInvoice\UserBundle\Tests\Form\Type;

use SolidInvoice\CoreBundle\Tests\FormTestCase;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Form\Type\UserType;

class UserTypeTest extends FormTestCase
{
    public function testSubmit()
    {
        $userName = $this->faker->userName;
        $email = $this->faker->email;
        $password = $this->faker->password;
        $phoneNumber = $this->faker->phoneNumber;

        $formData = [
            'username' => $userName,
            'email' => $email,
            'plainPassword' => [
                'first' => $password,
                'second' => $password,
            ],
            'mobile' => $phoneNumber,
        ];

        $object = new User();
        $object->setUsername($userName);
        $object->setEmail($email);
        $object->setPlainPassword($password);
        $object->setMobile($phoneNumber);

        $this->assertFormData(UserType::class, $formData, $object);
    }
}
