<?php

/*
 * This file is part of CSBill package.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\PaymentBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class PaymentMethodSettingsForm extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
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
        $options = array();

        switch ($settings['type']) {
            case 'password':
                $options['always_empty'] = false;
                break;

            case 'choice':
                $options['choices'] = $settings['options'];
                $options['placeholder'] = 'Please Choose';
                $options['attr'] = array('class' => 'select2');
                break;

            case 'checkbox':
                $options['required'] = false;
                break;
        }

        return $options;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setRequired(
            array(
                'settings',
            )
        );
    }

    /**
     * Returns the name of this type.
     *
     * @return string The name of this type
     */
    public function getName()
    {
        return 'method_settings';
    }
}
