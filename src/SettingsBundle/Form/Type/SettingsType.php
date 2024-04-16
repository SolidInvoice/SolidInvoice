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

namespace SolidInvoice\SettingsBundle\Form\Type;

use SolidInvoice\SettingsBundle\Entity\Setting;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @see \SolidInvoice\SettingsBundle\Tests\Form\Type\SettingsTypeTest
 */
class SettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        foreach ($options['settings'] as $key => $setting) {
            if (is_array($setting)) {
                $builder->add($key, self::class, ['settings' => $setting]);

                continue;
            }

            /** @var Setting $setting */
            $builder->add(
                $key,
                $setting->getType(),
                [
                    'help' => $setting->getDescription(),
                    'required' => false,
                    'data' => $setting->getValue(),
                ]
            );
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('settings');
    }

    public function getBlockPrefix(): string
    {
        return 'settings';
    }
}
