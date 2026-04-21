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

namespace SolidInvoice\InstallBundle\Tests\Form\Step;

use SolidInvoice\CoreBundle\Tests\FormTestCase;
use SolidInvoice\InstallBundle\DTO\UserAccount;
use SolidInvoice\InstallBundle\Form\Step\UserAccountStep;
use SolidInvoice\MoneyBundle\Form\Type\CurrencyType;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Intl\Locales;

/**
 * @covers \SolidInvoice\InstallBundle\Form\Step\UserAccountStep
 */
final class SystemInformationFormTest extends FormTestCase
{
    private RequestStack $requestStack;

    protected function setUp(): void
    {
        $this->requestStack = new RequestStack();

        parent::setUp();
    }

    public function testSubmit(): void
    {
        $locale = $this->faker->randomKey(Locales::getNames());
        $email = $this->faker->email;
        $firstName = $this->faker->firstName;
        $lastName = $this->faker->lastName;

        $formData = [
            'applicationUrl' => 'https://invoices.example.com',
            'locale' => $locale,
            'emailAddress' => $email,
            'firstName' => $firstName,
            'lastName' => $lastName,
            'password' => null,
        ];

        $this->assertFormData(
            $this->factory->create(UserAccountStep::class),
            $formData,
            new UserAccount(
                locale: $locale,
                firstName: $firstName,
                lastName: $lastName,
                emailAddress: $email,
                password: null,
            ),
        );
    }

    public function testSubmitWithPassword(): void
    {
        $locale = 'en';
        $email = $this->faker->email;
        $firstName = $this->faker->firstName;
        $lastName = $this->faker->lastName;
        $password = $this->faker->password(8, 20);

        $formData = [
            'applicationUrl' => 'https://invoices.example.com',
            'locale' => $locale,
            'emailAddress' => $email,
            'firstName' => $firstName,
            'lastName' => $lastName,
            'password' => $password,
        ];

        $this->assertFormData(
            $this->factory->create(UserAccountStep::class),
            $formData,
            new UserAccount(
                locale: $locale,
                firstName: $firstName,
                lastName: $lastName,
                emailAddress: $email,
                password: $password,
            ),
        );
    }

    public function testFormViewHasRequiredFields(): void
    {
        $form = $this->factory->create(UserAccountStep::class);
        $view = $form->createView();

        self::assertArrayHasKey('applicationUrl', $view->children);
        self::assertArrayHasKey('locale', $view->children);
        self::assertArrayHasKey('firstName', $view->children);
        self::assertArrayHasKey('lastName', $view->children);
        self::assertArrayHasKey('emailAddress', $view->children);
        self::assertArrayHasKey('password', $view->children);
    }

    public function testApplicationUrlFieldDefaultsToCurrentRequestHost(): void
    {
        $this->requestStack->push(Request::create('https://invoices.example.com/install'));

        $form = $this->factory->create(UserAccountStep::class);
        $view = $form->createView();

        self::assertSame('https://invoices.example.com', $view->children['applicationUrl']->vars['value']);
    }

    public function testConfigureOptions(): void
    {
        $form = $this->factory->create(UserAccountStep::class, new UserAccount());

        self::assertInstanceOf(UserAccount::class, $form->getData());
    }

    public function testEmailFieldIsEmailType(): void
    {
        $form = $this->factory->create(UserAccountStep::class);
        $view = $form->createView();

        self::assertContains('email', $view->children['emailAddress']->vars['block_prefixes']);
    }

    public function testPasswordFieldIsPasswordType(): void
    {
        $form = $this->factory->create(UserAccountStep::class);
        $view = $form->createView();

        self::assertContains('password', $view->children['password']->vars['block_prefixes']);
    }

    public function testPasswordFieldHasCorrectClass(): void
    {
        $form = $this->factory->create(UserAccountStep::class);
        $view = $form->createView();

        self::assertSame('password-field', $view->children['password']->vars['attr']['class']);
    }

    public function testLocaleFieldIsChoiceTypeWhenIntlLoaded(): void
    {
        if (! extension_loaded('intl')) {
            self::markTestSkipped('intl extension not loaded');
        }

        $form = $this->factory->create(UserAccountStep::class);
        $view = $form->createView();

        self::assertSame('choice', $view->children['locale']->vars['block_prefixes'][1]);
    }

    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension([new UserAccountStep($this->requestStack), new CurrencyType('en')], []),
        ];
    }
}
