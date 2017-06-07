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
use CSBill\NotificationBundle\Form\Type\NotificationType;
use CSBill\SettingsBundle\Entity\Setting;
use CSBill\SettingsBundle\Form\Type\SettingsType;
use Mockery as M;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class SettingsTypeTest extends FormTestCase
{
    public function testSubmit()
    {
        $formData = [];
        $object = [];
        $settings = [];

        foreach ([EmailType::class, NotificationType::class, ImageUploadType::class, PasswordType::class, TextType::class] as $i => $type) {
            $setting = new Setting();
            $setting->setKey('setting_'.$i);
            $setting->setType($type);

            $value = $this->faker->name;
            $formValue = $value;

            if (NotificationType::class === $type) {
                $value = [
                    'email' => $this->faker->boolean,
                    'hipchat' => $this->faker->boolean,
                    'sms' => $this->faker->boolean,
                ];

                $formValue = json_encode($value);
            }

            $formData['setting_'.$i] = $value;
            $object['setting_'.$i] = $formValue;

            $settings['setting_'.$i] = $setting;
        }

        $options = [
            'settings' => $settings,
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
