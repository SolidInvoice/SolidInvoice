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

namespace CSBill\PaymentBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PaymentMethodSettingsType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $settings = $options['settings'];

        foreach ($settings as $setting) {
            $builder->add($setting['name'], $this->getType($setting['type']), $this->getOptions($setting));
        }
    }

    /**
     * @param array $settings
     *
     * @return array
     */
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

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['settings']);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'method_settings';
    }

    private function getType($type)
    {
        switch ($type) {
            case 'password':
                return PasswordType::class;

            case 'choice':
                return ChoiceType::class;

            case 'checkbox':
                return CheckboxType::class;

            case 'text':
            default:
                return TextType::class;
        }
    }
}
