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

use Mockery as M;
use SolidInvoice\CoreBundle\Tests\FormTestCase;
use SolidInvoice\UserBundle\DTO\InvitedRegistration;
use SolidInvoice\UserBundle\Form\Type\InvitedRegisterType;
use Symfony\Contracts\Translation\TranslatorInterface;

final class InvitedRegisterTypeTest extends FormTestCase
{
    public function testSubmit(): void
    {
        $email = $this->faker->email;
        $firstName = $this->faker->firstName;
        $lastName = $this->faker->lastName;
        $password = $this->faker->password(8);

        $formData = [
            'email' => $email,
            'firstName' => $firstName,
            'lastName' => $lastName,
            'plainPassword' => $password,
            'acceptTerms' => true,
        ];

        $object = new InvitedRegistration();
        $object->email = $email;
        $object->firstName = $firstName;
        $object->lastName = $lastName;
        $object->plainPassword = $password;
        $object->acceptTerms = true;

        $this->assertFormData(
            InvitedRegisterType::class,
            $formData,
            $object,
            ['email' => $email]
        );
    }

    public function testEmailFieldIsReadonly(): void
    {
        $email = $this->faker->email;
        $form = $this->factory->create(InvitedRegisterType::class, null, ['email' => $email]);

        self::assertTrue($form->has('email'));

        $emailField = $form->get('email');
        self::assertTrue($emailField->getConfig()->getOption('attr')['readonly']);
        self::assertSame($email, $emailField->getData());
    }

    public function testFormDoesNotHaveCompanyField(): void
    {
        $form = $this->factory->create(
            InvitedRegisterType::class,
            null,
            ['email' => $this->faker->email]
        );

        self::assertFalse($form->has('company'));
    }

    public function testFormHasRequiredFields(): void
    {
        $form = $this->factory->create(
            InvitedRegisterType::class,
            null,
            ['email' => $this->faker->email]
        );

        self::assertTrue($form->has('email'));
        self::assertTrue($form->has('firstName'));
        self::assertTrue($form->has('lastName'));
        self::assertTrue($form->has('plainPassword'));
        self::assertTrue($form->has('acceptTerms'));
    }

    protected function getTypes(): array
    {
        $translator = M::mock(TranslatorInterface::class);
        $translator->shouldReceive('trans')
            ->andReturnUsing(fn ($key) => $key);

        return array_merge(parent::getTypes(), [
            InvitedRegisterType::class => new InvitedRegisterType($translator),
        ]);
    }
}
