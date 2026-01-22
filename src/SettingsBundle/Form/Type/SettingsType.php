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
use function array_walk;
use function is_array;
use function ksort;

/**
 * @see \SolidInvoice\SettingsBundle\Tests\Form\Type\SettingsTypeTest
 */
class SettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        foreach ($options['settings'] as $key => $setting) {
            if (is_array($setting)) {
                $builder->add($key, self::class, [
                    'settings' => $setting,
                    'subscription_in_trial' => $options['subscription_in_trial'],
                ]);

                continue;
            }

            /** @var Setting $setting */
            $fieldOptions = [
                'help' => $setting->getDescription(),
                'required' => false,
            ];

            // Force default value during trial for trial-restricted fields
            $formOptions = $setting->getFormOptions();
            ksort($formOptions);
            array_walk($formOptions, static function (&$value): void {
                if (is_array($value)) {
                    ksort($value);
                }
            });

            $isTrialRestricted = $formOptions['trial_restricted'] ?? false;

            if ($isTrialRestricted && $options['subscription_in_trial']) {
                // Use default value instead of stored value during trial
                $fieldOptions['data'] = $setting->getDefaultValue();
            } else {
                // Use stored value normally
                $fieldOptions['data'] = $setting->getValue();
            }

            // Add trial-related options that will be handled by TrialRestrictedExtension
            $fieldOptions['subscription_in_trial'] = $options['subscription_in_trial'];
            $fieldOptions['trial_restricted'] = $isTrialRestricted;

            // Merge remaining form options from Config (excluding our trial-specific options)
            $additionalOptions = array_diff_key($formOptions, ['trial_restricted' => true]);
            $fieldOptions = array_merge($fieldOptions, $additionalOptions);

            $builder->add($key, $setting->getType(), $fieldOptions);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('settings');
        $resolver->setDefaults(['subscription_in_trial' => false]);
        $resolver->setAllowedTypes('subscription_in_trial', 'bool');
    }

    public function getBlockPrefix(): string
    {
        return 'settings';
    }
}
