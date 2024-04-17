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

namespace SolidInvoice\SettingsBundle\Tests\Form\Type;

use SolidInvoice\CoreBundle\Form\Type\ImageUploadType;
use SolidInvoice\CoreBundle\Tests\FormTestCase;
use SolidInvoice\NotificationBundle\Form\Type\NotificationType;
use SolidInvoice\SettingsBundle\Entity\Setting;
use SolidInvoice\SettingsBundle\Form\Type\MailTransportType;
use SolidInvoice\SettingsBundle\Form\Type\SettingsType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\UX\StimulusBundle\Helper\StimulusHelper;

class SettingsTypeTest extends FormTestCase
{
    public function testSubmit(): void
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
                MailTransportType::class,
            ] as $i => $type
        ) {
            $setting = new Setting();
            $setting->setKey('setting_' . $i);
            $setting->setType($type);

            $value = $this->faker->name;
            $formValue = $value;
            if (NotificationType::class === $type) {
                $value = [
                    'email' => $this->faker->boolean,
                    'sms' => $this->faker->boolean,
                ];
                $formValue = json_encode($value, JSON_THROW_ON_ERROR);
            }

            if (ImageUploadType::class === $type) {
                break;
            }

            $formData['setting_' . $i] = $value;
            $object['setting_' . $i] = $formValue;

            $settings['setting_' . $i] = $setting;
        }

        $options = [
            'settings' => $settings,
        ];

        $this->assertFormData($this->factory->create(SettingsType::class, null, $options), $formData, $object);
    }

    protected function getTypes(): array
    {
        $extensions = parent::getTypes();

        $extensions[] = new MailTransportType([], new StimulusHelper(null));

        return $extensions;
    }
}
