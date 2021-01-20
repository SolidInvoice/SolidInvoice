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

namespace SolidInvoice\MailerBundle\Tests\Form\Type\TransportConfig;

use SolidInvoice\CoreBundle\Tests\FormTestCase;
use SolidInvoice\MailerBundle\Form\Type\TransportConfig\SmtpTransportConfigType;

class SmtpTransportConfigTypeTest extends FormTestCase
{
    public function testSubmit(): void
    {
        $formData = [
            'host' => 'example.com',
            'port' => '465',
            'user' => 'user',
            'password' => 'pass',
        ];

        $this->assertFormData(SmtpTransportConfigType::class, $formData, ['host' => 'example.com', 'port' => '465', 'user' => 'user', 'password' => 'pass']);
    }
}
