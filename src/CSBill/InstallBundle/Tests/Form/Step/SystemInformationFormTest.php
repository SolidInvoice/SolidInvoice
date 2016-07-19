<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\InstallBundle\Tests\Form\Step;

use CSBill\CoreBundle\Tests\FormTestCase;
use CSBill\InstallBundle\Form\Step\SystemInformationForm;
use Faker\Factory;
use Mockery as M;
use Symfony\Component\Intl\Intl;

class SystemInformationFormTest extends FormTestCase
{
    public function testSubmitData()
    {
        $faker = Factory::create();

        $formData = [
            'locale' => $faker->randomKey(Intl::getLocaleBundle()->getLocaleNames()),
            'currency' => $faker->randomKey(Intl::getCurrencyBundle()->getCurrencyNames()),
            'username' => $faker->userName,
            'email_address' => $faker->email,
            'base_url' => $faker->url,
            'password' => null,
        ];

        $form = $this->factory->create(SystemInformationForm::class, null, ['userCount' => 0]);

        // submit the data to the form directly
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($formData, $form->getData());

        $view = $form->createView();
        $children = $view->children;

        foreach (array_keys($formData) as $key) {
            $this->assertArrayHasKey($key, $children);
        }
    }
}
