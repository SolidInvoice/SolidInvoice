<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\InstallBundle\Tests\Form\Type;

use CSBill\CoreBundle\Tests\FormTestCase;
use CSBill\InstallBundle\Form\Type\DatabaseConfigType;

class DatabaseConfigTypeTest extends FormTestCase
{
    public function testSubmitValidData()
    {
        $drivers = array(
            'pdo_mysql' => 'MySQL',
        );

        $formData = array(
            'driver' => 'pdo_mysql',
            'host' => 'localhost',
            'port' => 1234,
            'user' => 'root',
            'password' => 'password',
            'name' => 'testdb',
        );

        $type = new DatabaseConfigType();
        $form = $this->factory->create($type, null, array('drivers' => $drivers));

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
