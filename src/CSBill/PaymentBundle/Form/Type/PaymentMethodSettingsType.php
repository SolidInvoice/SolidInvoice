<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\PaymentBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
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
            $options = $this->getOptions($setting);
            $builder->add($setting['name'], $setting['type'], $options);
        }
    }

    /**
     * @param array $settings
     *
     * @return array
     */
    private function getOptions(array $settings)
    {
        $options = [];

        switch ($settings['type']) {
            case 'password':
                $options['always_empty'] = false;
                break;

            case 'choice':
                $options['choices'] = $settings['options'];
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
}
