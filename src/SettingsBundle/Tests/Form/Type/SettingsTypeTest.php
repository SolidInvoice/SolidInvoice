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

namespace SolidInvoice\SettingsBundle\Tests\Form\Type;

use SolidInvoice\CoreBundle\Form\Type\ImageUploadType;
use SolidInvoice\CoreBundle\Tests\FormTestCase;
use SolidInvoice\NotificationBundle\Form\Type\NotificationType;
use SolidInvoice\SettingsBundle\Entity\Setting;
use SolidInvoice\SettingsBundle\Form\Type\MailEncryptionType;
use SolidInvoice\SettingsBundle\Form\Type\MailFormatType;
use SolidInvoice\SettingsBundle\Form\Type\MailTransportType;
use SolidInvoice\SettingsBundle\Form\Type\SettingsType;
use Defuse\Crypto\Key;
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

        foreach (
            [
                EmailType::class,
                NotificationType::class,
                ImageUploadType::class,
                PasswordType::class,
                TextType::class,
                MailEncryptionType::class,
                MailFormatType::class,
                MailTransportType::class,
            ] as $i => $type
        ) {
            $setting = new Setting();
            $setting->setKey('setting_'.$i);
            $setting->setType($type);

            $value = $this->faker->name;
            $formValue = $value;

            switch (true) {
                case NotificationType::class === $type:
                    $value = [
                        'email' => $this->faker->boolean,
                        'sms' => $this->faker->boolean,
                    ];

                    $formValue = json_encode($value);
                    break;

                case MailEncryptionType::class === $type:
                    $value = $formValue = 'ssl';
                    break;

                case MailFormatType::class === $type:
                    $value = $formValue = 'html';
                    break;

                case MailTransportType::class === $type:
                    $value = $formValue = 'smtp';
                    break;
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

        $type = new ImageUploadType($session, Key::createNewRandomKey()->saveToAsciiSafeString());

        return [
            new PreloadedExtension([$type], []),
        ];
    }
}
