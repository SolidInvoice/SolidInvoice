<?php
/**
 * This file is part of the CSBill project.
 * 
 * @author      MiWay Development Team
 * @copyright   Copyright (c) 2014 MiWay Insurance Ltd
 */

namespace CSBill\InstallBundle\Tests\Form\Type;

use CSBill\CoreBundle\Tests\FormTestCase;
use CSBill\InstallBundle\Form\Type\DatabaseConfigType;
use CSBill\InstallBundle\Form\Type\EmailSettingsType;

class EmailSettingsTypeTest extends FormTestCase
{
    private $transports = array(
        'mail' => 'PHP Mail',
        'sendmail' => 'Sendmail',
        'smtp' => 'SMTP',
        'gmail' => 'Gmail',
    );

    /**
     * @dataProvider formData
     *
     * @param array $formData
     * @param array $assert
     */
    public function testMailSettings(array $formData, array $assert)
    {
        $type = new EmailSettingsType();
        $form = $this->factory->create($type, null, array('transports' => $this->transports));

        // submit the data to the form directly
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($assert, $form->getData());

        $view = $form->createView();
        $children = $view->children;

        foreach (array_keys($formData) as $key) {
            $this->assertArrayHasKey($key, $children);
        }
    }

    /**
     * @return array
     */
    public function formData()
    {
        return array(
            array(
                array(
                    'transport' => 'mail',
                    'host' => 'localhost',
                    'port' => 1234,
                    'encryption' => 'ssl',
                    'user' => 'root',
                    'password' => 'password',
                ),
                array(
                    'transport' => 'mail',
                    'host' => null,
                    'port' => null,
                    'encryption' => null,
                    'user' => null,
                    'password' => null,
                ),
            ),
            array(
                array(
                    'transport' => 'sendmail',
                    'host' => 'localhost',
                    'port' => 1234,
                    'encryption' => 'ssl',
                    'user' => 'root',
                    'password' => 'password',
                ),
                array(
                    'transport' => 'sendmail',
                    'host' => null,
                    'port' => null,
                    'encryption' => null,
                    'user' => null,
                    'password' => null,
                ),
            ),
            array(
                array(
                    'transport' => 'smtp',
                    'host' => 'localhost',
                    'port' => 1234,
                    'encryption' => 'ssl',
                    'user' => 'root',
                    'password' => 'password',
                ),
                array(
                    'transport' => 'smtp',
                    'host' => 'localhost',
                    'port' => 1234,
                    'encryption' => 'ssl',
                    'user' => 'root',
                    'password' => 'password',
                ),
            ),
            array(
                array(
                    'transport' => 'gmail',
                    'host' => 'localhost',
                    'port' => 1234,
                    'encryption' => 'ssl',
                    'user' => 'root',
                    'password' => 'password',
                ),
                array(
                    'transport' => 'gmail',
                    'host' => null,
                    'port' => null,
                    'encryption' => null,
                    'user' => 'root',
                    'password' => 'password',
                ),
            ),
        );
    }
}
