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
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Form\Type\ProfileType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Forms;

final class ProfileTypeTest extends FormTestCase
{
    public function testSubmit(): void
    {
        $mobile = $this->faker->phoneNumber;

        $formData = [
            'firstName' => $this->faker->firstName,
            'lastName' => $this->faker->lastName,
            'mobile' => $mobile,
        ];

        $object = new User();
        $object->setMobile($mobile);

        $this->assertFormData($this->createForm(new ModeResolver(), $object), $formData, $object);
    }

    public function testEmailAndCurrentPasswordAreNotDisabledWhenNotInDemoMode(): void
    {
        $form = $this->createForm(new ModeResolver(), new User());

        self::assertFalse($form->get('email')->isDisabled());
        self::assertFalse($form->get('current_password')->isDisabled());
    }

    public function testEmailAndCurrentPasswordAreDisabledInDemoMode(): void
    {
        $form = $this->createForm(new ModeResolver('demo', 'demo@example.com', 'demo-password'), new User());

        self::assertTrue($form->get('email')->isDisabled());
        self::assertTrue($form->get('current_password')->isDisabled());
    }

    /**
     * Builds a ProfileType form using a real ModeResolver, since the form type now requires one
     * injected via its constructor (it is no longer resolvable as a bare class-name string).
     *
     * @return FormInterface<User>
     */
    private function createForm(ModeResolver $modeResolver, User $object): FormInterface
    {
        $factory = Forms::createFormFactoryBuilder()
            ->addTypeExtensions($this->getTypeExtensions())
            ->addType(new ProfileType($modeResolver))
            ->getFormFactory();

        return $factory->create(ProfileType::class, $object);
    }
}
