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

namespace SolidInvoice\PaymentBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @see \SolidInvoice\PaymentBundle\Tests\Form\Type\PaymentMethodSettingsTypeTest
 */
class PaymentMethodSettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $settings = $options['settings'];

        foreach ($settings as $setting) {
            $builder->add($setting['name'], $this->getType($setting['type']), $this->getOptions($setting));
        }
    }

    private function getOptions(array $settings): array
    {
        $options = [];

        switch ($settings['type']) {
            case 'password':
                $options['always_empty'] = false;

                break;

            case 'choice':
                $options['choices'] = array_flip($settings['options']);
                $options['placeholder'] = 'Please Choose';
                $options['attr'] = ['class' => 'select2'];

                break;

            case 'checkbox':
                $options['required'] = false;

                break;
        }

        return $options;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(['settings']);
    }

    public function getBlockPrefix(): string
    {
        return 'method_settings';
    }

    private function getType($type): string
    {
        return match ($type) {
            'password' => PasswordType::class,
            'choice' => ChoiceType::class,
            'checkbox' => CheckboxType::class,
            default => TextType::class,
        };
    }
}
