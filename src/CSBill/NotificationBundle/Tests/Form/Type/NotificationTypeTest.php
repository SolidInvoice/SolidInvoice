<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\NotificationBundle\Tests\Form\Type;

use CSBill\NotificationBundle\Form\Type\NotificationType;
use Symfony\Component\Form\Test\TypeTestCase;

class NotificationTypeTest extends TypeTestCase
{
    public function testSubmitValidData()
    {
        $formData = [
            'email' => true,
            'hipchat' => false,
            'sms' => true,
        ];

        $type = new NotificationType();
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
