<?php
/**
 * This file is part of the CSBill project.
 * 
 * @author      MiWay Development Team
 * @copyright   Copyright (c) 2014 MiWay Insurance Ltd
 */

namespace CSBill\InstallBundle\Tests\Form\Step;

use CSBill\CoreBundle\Tests\FormTestCase;
use CSBill\InstallBundle\Form\Step\SystemInformationForm;
use Faker\Factory;
use Symfony\Component\Intl\Intl;

class SystemInformationFormTest extends FormTestCase
{
    public function testSubmitData()
    {
        $faker = Factory::create();

        $formData = array(
            'locale' => $faker->randomKey(Intl::getLocaleBundle()->getLocaleNames()),
            'currency' => $faker->randomKey(Intl::getCurrencyBundle()->getCurrencyNames()),
            'username' => $faker->userName,
            'email_address' => $faker->email,
            'password' => null
        );

        $type = new SystemInformationForm();
        $form = $this->factory->create($type);

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
