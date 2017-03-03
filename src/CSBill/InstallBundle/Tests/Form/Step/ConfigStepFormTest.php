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
    public function testSubmit()
    {
        $drivers = [
            'pdo_mysql' => 'MySQL',
        ];

        $transports = [
            'mail' => 'PHPMail',
        ];

        $formData = [
            'database_config' => [
                'driver' => 'pdo_mysql',
                'host' => 'localhost',
                'port' => 1234,
                'user' => 'root',
                'password' => 'password',
                'name' => 'testdb',
            ],
            'email_settings' => [
                'transport' => 'mail',
                'host' => null,
                'port' => null,
                'encryption' => null,
                'user' => null,
                'password' => null,
            ],
        ];

        $this->assertFormData($this->factory->create(ConfigStepForm::class, null, ['drivers' => $drivers, 'mailer_transports' => $transports]), $formData, $formData);
    }
}
