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
use CSBill\InstallBundle\Form\Step\ConfigStepForm;

class ConfigStepFormTest extends FormTestCase
{
    public function testSubmitData()
    {
        $drivers = array(
            'pdo_mysql' => 'MySQL',
        );

        $transports = array(
            'mail' => 'PHPMail',
        );

        $formData = array(
            'database_config' => array(
                'driver' => 'pdo_mysql',
                'host' => 'localhost',
                'port' => 1234,
                'user' => 'root',
                'password' => 'password',
                'name' => 'testdb',
            ),
            'email_settings' => array(
                'transport' => 'mail',
                'host' => null,
                'port' => null,
                'encryption' => null,
                'user' => null,
                'password' => null,
            ),
        );

        $type = new ConfigStepForm();
        $form = $this->factory->create($type, null, array('drivers' => $drivers, 'mailer_transports' => $transports));

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
