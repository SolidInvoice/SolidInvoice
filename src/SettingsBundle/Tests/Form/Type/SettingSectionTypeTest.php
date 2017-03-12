<?php

declare(strict_types=1);
/**
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\SettingsBundle\Tests\Form\Type;

use CSBill\CoreBundle\Tests\FormTestCase;
use CSBill\SettingsBundle\Form\Type\SettingSectionType;
use CSBill\SettingsBundle\Manager\SettingsManager;
use Mockery as M;

class SettingSectionTypeTest extends FormTestCase
{
    public function testSubmit()
    {
        $formData = [
            'one' => [
                'two' => 1,
            ],
        ];

        $object = ['one' => []];

        $manager = M::mock(SettingsManager::class);
        $manager->shouldReceive('getSettings')
            ->andReturn(['one' => []]);

        $manager->shouldReceive('get')
            ->andReturn(['two' => []]);

        $this->assertFormData($this->factory->create(SettingSectionType::class, null, ['manager' => $manager]), $formData, $object);
    }
}
