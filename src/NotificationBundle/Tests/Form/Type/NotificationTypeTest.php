<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\NotificationBundle\Tests\Form\Type;

use CSBill\CoreBundle\Tests\FormTestCase;
use CSBill\NotificationBundle\Form\Type\NotificationType;

class NotificationTypeTest extends FormTestCase
{
    public function testSubmit()
    {
        $formData = [
            'email' => $this->faker->boolean,
            'hipchat' => $this->faker->boolean,
            'sms' => $this->faker->boolean,
        ];

        $this->assertFormData(NotificationType::class, $formData, $formData);
    }
}
