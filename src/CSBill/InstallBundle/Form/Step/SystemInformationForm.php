<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\InstallBundle\Form\Step;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Intl\Intl;
use Symfony\Component\Validator\Constraints;

class SystemInformationForm extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $currencies = Intl::getCurrencyBundle()->getCurrencyNames();

        if (extension_loaded('intl')) {
            $builder->add(
                'locale',
                'select2',
                array(
                    'choices' => array_flip(Intl::getLocaleBundle()->getLocaleNames()),
                    'constraints' => new Constraints\NotBlank(array('message' => 'Please select a locale')),
                    'placeholder' => '',
                    'choices_as_values' => true,
                )
            );
        } else {
            $builder->add(
                'locale',
                null,
                array(
                    'data' => 'en',
                    'read_only' => true,
                    'help' => 'The only currently supported locale is "en". To choose a different locale, please install the \'intl\' extension',
                )
            );
        }

        $builder->add(
            'currency',
            'select2',
            array(
                'choices' => array_flip($currencies),
                'constraints' => new Constraints\NotBlank(array('message' => 'Please select a currency')),
                'placeholder' => '',
                'choices_as_values' => true,
            )
        );

        $builder->add(
            'username',
            null,
            array(
                'constraints' => new Constraints\NotBlank(array('message' => 'Please enter a username')),
            )
        );

        $builder->add(
            'email_address',
            'email',
            array(
                'constraints' => array(
                    new Constraints\NotBlank(array('message' => 'Please enter a email')),
                    new Constraints\Email(),
                ),
            )
        );

        $builder->add(
            'password',
            'repeated',
            array(
                'type' => 'password',
                'invalid_message' => 'The password fields must match.',
                'options' => array('attr' => array('class' => 'password-field')),
                'required' => true,
                'first_options' => array('label' => 'Password'),
                'second_options' => array('label' => 'Repeat Password'),
                'constraints' => array(
                    new Constraints\NotBlank(array('message' => 'You must enter a secure password')),
                    new Constraints\Length(array('min' => 6)),
                ),
            )
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'system_information';
    }
}
