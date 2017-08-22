<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\InstallBundle\Tests\Form\Step;

use SolidInvoice\CoreBundle\Tests\FormTestCase;
use SolidInvoice\InstallBundle\Form\Step\SystemInformationForm;
use SolidInvoice\MoneyBundle\Form\Type\CurrencyType;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Intl\Intl;

class SystemInformationFormTest extends FormTestCase
{
    public function testSubmit()
    {
        $formData = [
            'locale' => $this->faker->randomKey(Intl::getLocaleBundle()->getLocaleNames()),
            'username' => $this->faker->userName,
            'email_address' => $this->faker->email,
            'base_url' => $this->faker->url,
            'password' => null,
            'currency' => 'USD',
        ];

        $this->assertFormData($this->factory->create(SystemInformationForm::class, null, ['userCount' => 0]), $formData, $formData);
    }

    protected function getExtensions()
    {
        return [
            new PreloadedExtension([new CurrencyType('en')], []),
        ];
    }
}
