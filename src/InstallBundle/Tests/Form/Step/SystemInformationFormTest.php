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
use Symfony\Component\Intl\Locales;

class SystemInformationFormTest extends FormTestCase
{
    public function testSubmit(): void
    {
        $locale = $this->faker->randomKey(Locales::getNames());
        $email = $this->faker->email;
        $firstName = $this->faker->firstName;
        $lastName = $this->faker->lastName;

        $formData = [
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

    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension([new CurrencyType('en')], []),
        ];
    }
}
