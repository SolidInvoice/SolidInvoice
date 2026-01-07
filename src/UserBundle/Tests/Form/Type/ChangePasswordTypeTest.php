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
use SolidInvoice\UserBundle\DTO\ChangePassword;
use SolidInvoice\UserBundle\Form\Type\ChangePasswordType;

class ChangePasswordTypeTest extends FormTestCase
{
    public function testSubmit(): void
    {
        $currentPassword = $this->faker->password;
        $newPassword = $this->faker->password;

        $formData = [
            'currentPassword' => $currentPassword,
            'plainPassword' => [
                'first' => $newPassword,
                'second' => $newPassword,
            ],
        ];

        $object = new ChangePassword();
        $object->currentPassword = $currentPassword;
        $object->plainPassword = $newPassword;

        $this->assertFormData(ChangePasswordType::class, $formData, $object, [
            'confirm_password' => true,
        ]);
    }

    public function testSubmitWithoutCurrentPassword(): void
    {
        $newPassword = $this->faker->password;

        $formData = [
            'plainPassword' => [
                'first' => $newPassword,
                'second' => $newPassword,
            ],
        ];

        $object = new ChangePassword();
        $object->plainPassword = $newPassword;

        $this->assertFormData(ChangePasswordType::class, $formData, $object, [
            'confirm_password' => false,
        ]);
    }

    public function testFormHasExpectedFields(): void
    {
        $form = $this->factory->create(ChangePasswordType::class, null, [
            'confirm_password' => true,
        ]);

        self::assertTrue($form->has('currentPassword'));
        self::assertTrue($form->has('plainPassword'));
    }

    public function testFormWithoutCurrentPasswordField(): void
    {
        $form = $this->factory->create(ChangePasswordType::class, null, [
            'confirm_password' => false,
        ]);

        self::assertFalse($form->has('currentPassword'));
        self::assertTrue($form->has('plainPassword'));
    }

    public function testRepeatedPasswordFieldStructure(): void
    {
        $form = $this->factory->create(ChangePasswordType::class);

        self::assertTrue($form->get('plainPassword')->has('first'));
        self::assertTrue($form->get('plainPassword')->has('second'));
    }
}
