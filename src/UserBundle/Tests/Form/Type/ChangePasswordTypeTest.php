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

use SolidInvoice\CoreBundle\Mode\ModeResolver;
use SolidInvoice\CoreBundle\Tests\FormTestCase;
use SolidInvoice\UserBundle\DTO\ChangePassword;
use SolidInvoice\UserBundle\Form\Type\ChangePasswordType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Forms;

final class ChangePasswordTypeTest extends FormTestCase
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

        $form = $this->createForm(new ModeResolver(), $object, [
            'confirm_password' => true,
        ]);

        $this->assertFormData($form, $formData, $object);
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

        $form = $this->createForm(new ModeResolver(), $object, [
            'confirm_password' => false,
        ]);

        $this->assertFormData($form, $formData, $object);
    }

    public function testFormHasExpectedFields(): void
    {
        $form = $this->createForm(new ModeResolver(), null, [
            'confirm_password' => true,
        ]);

        self::assertTrue($form->has('currentPassword'));
        self::assertTrue($form->has('plainPassword'));
    }

    public function testFormWithoutCurrentPasswordField(): void
    {
        $form = $this->createForm(new ModeResolver(), null, [
            'confirm_password' => false,
        ]);

        self::assertFalse($form->has('currentPassword'));
        self::assertTrue($form->has('plainPassword'));
    }

    public function testRepeatedPasswordFieldStructure(): void
    {
        $form = $this->createForm(new ModeResolver(), null);

        self::assertTrue($form->get('plainPassword')->has('first'));
        self::assertTrue($form->get('plainPassword')->has('second'));
    }

    public function testCurrentPasswordAndPlainPasswordAreNotDisabledWhenNotInDemoMode(): void
    {
        $form = $this->createForm(new ModeResolver(), null, [
            'confirm_password' => true,
        ]);

        self::assertFalse($form->get('currentPassword')->isDisabled());
        self::assertFalse($form->get('plainPassword')->isDisabled());
    }

    public function testCurrentPasswordAndPlainPasswordAreDisabledInDemoMode(): void
    {
        $form = $this->createForm(new ModeResolver('demo', 'demo@example.com', 'demo-password'), null, [
            'confirm_password' => true,
        ]);

        self::assertTrue($form->get('currentPassword')->isDisabled());
        self::assertTrue($form->get('plainPassword')->isDisabled());
    }

    /**
     * Builds a ChangePasswordType form using a real ModeResolver, since the form type now requires
     * one injected via its constructor (it is no longer resolvable as a bare class-name string).
     *
     * @param array<string, mixed> $options
     *
     * @return FormInterface<ChangePassword>
     */
    private function createForm(ModeResolver $modeResolver, ?ChangePassword $object, array $options = []): FormInterface
    {
        $factory = Forms::createFormFactoryBuilder()
            ->addTypeExtensions($this->getTypeExtensions())
            ->addType(new ChangePasswordType($modeResolver))
            ->getFormFactory();

        return $factory->create(ChangePasswordType::class, $object, $options);
    }
}
