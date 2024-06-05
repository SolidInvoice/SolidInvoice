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
use SolidInvoice\InstallBundle\Form\Step\SystemInformationForm;
use SolidInvoice\MoneyBundle\Form\Type\CurrencyType;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Intl\Locales;

class SystemInformationFormTest extends FormTestCase
{
    public function testSubmit(): void
    {
        $formData = [
            'locale' => $this->faker->randomKey(Locales::getNames()),
            'email_address' => $this->faker->email,
            'password' => null,
        ];

        $this->assertFormData($this->factory->create(SystemInformationForm::class, null, ['userCount' => 0]), $formData, $formData);
    }

    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension([new CurrencyType('en')], []),
        ];
    }
}
