<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\MailerBundle\Tests\Form\Type\TransportConfig;

use SolidInvoice\CoreBundle\Tests\FormTestCase;
use SolidInvoice\MailerBundle\Form\Type\TransportConfig\SesTransportConfigType;

class SesTransportConfigTypeTest extends FormTestCase
{
    public function testSubmit(): void
    {
        $formData = [
            'accessKey' => 'foobar',
            'accessSecret' => 'baz',
            'region' => 'eu',
        ];

        $this->assertFormData(SesTransportConfigType::class, $formData, ['accessKey' => 'foobar', 'accessSecret' => 'baz', 'region' => 'eu']);
    }
}
