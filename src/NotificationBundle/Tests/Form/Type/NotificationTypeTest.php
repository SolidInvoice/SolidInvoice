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

namespace SolidInvoice\NotificationBundle\Tests\Form\Type;

use SolidInvoice\CoreBundle\Tests\FormTestCase;
use SolidInvoice\NotificationBundle\Form\Type\NotificationType;

class NotificationTypeTest extends FormTestCase
{
    public function testSubmit()
    {
        $formData = [
            'email' => $this->faker->boolean,
            'sms' => $this->faker->boolean,
        ];

        $this->assertFormData(NotificationType::class, $formData, json_encode($formData, JSON_THROW_ON_ERROR));
    }
}
