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

namespace SolidInvoice\InstallBundle\Tests\Form\Type;

use SolidInvoice\CoreBundle\Tests\FormTestCase;
use SolidInvoice\InstallBundle\Form\Type\DatabaseConfigType;

class DatabaseConfigTypeTest extends FormTestCase
{
    public function testSubmit(): void
    {
        $drivers = [
            'pdo_mysql' => 'MySQL',
        ];

        $formData = [
            'driver' => 'pdo_mysql',
            'host' => '127.0.0.1',
            'port' => 1234,
            'user' => 'root',
            'password' => 'password',
            'name' => 'testdb',
        ];

        $this->assertFormData($this->factory->create(DatabaseConfigType::class, null, ['drivers' => $drivers]), $formData, $formData);
    }
}
