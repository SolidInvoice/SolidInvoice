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

namespace CSBill\SettingsBundle\Tests\Form\Type;

use CSBill\CoreBundle\Form\Type\ImageUploadType;
use CSBill\CoreBundle\Security\Encryption;
use CSBill\CoreBundle\Tests\FormTestCase;
use CSBill\SettingsBundle\Entity\Setting;
use CSBill\SettingsBundle\Form\Type\SettingsType;
use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class SettingsTypeTest extends FormTestCase
{
    public function testSubmit()
    {
        $formData = [];
        $object = [];
        $settings = [];

        foreach (['select2', 'choice', 'radio', 'email', 'checkbox', 'notification', 'image_upload', 'password', 'text'] as $i => $type) {
            $setting = new Setting();
            $setting->setKey('setting_'.$i);
            $setting->setType($type);

            $value = $this->faker->name;

            if (in_array($type, ['select2', 'choice'], true)) {
                $values = $this->faker->rgbColorAsArray;
                $setting->setOptions($values);

                $value = $this->faker->randomKey($values);
            }

            if ('radio' === $type) {
                $setting->setOptions([$this->faker->name, $this->faker->name]);
                $value = 0;
            }

            if ('checkbox' === $type) {
                $value = $this->faker->boolean;
            }

            if ('notification' === $type) {
                $value = [
                    'email' => $this->faker->boolean,
                    'hipchat' => $this->faker->boolean,
                    'sms' => $this->faker->boolean,
                ];
            }

            $formData['setting_'.$i] = $value;
            $object['setting_'.$i] = $value;

            $settings[] = $setting;
        }

        $options = [
            'section' => $settings,
        ];

        $this->assertFormData($this->factory->create(SettingsType::class, null, $options), $formData, $object);
    }

    protected function getExtensions()
    {
        $session = M::mock(SessionInterface::class);
        $session->shouldReceive('getId')
            ->andReturn($this->faker->md5);

        $encryption = M::mock(Encryption::class);
        $encryption->shouldReceive('encrypt')
            ->andReturn($this->faker->md5);

        $type = new ImageUploadType($session, $encryption);

        return [
            new PreloadedExtension([$type], []),
        ];
    }
}
