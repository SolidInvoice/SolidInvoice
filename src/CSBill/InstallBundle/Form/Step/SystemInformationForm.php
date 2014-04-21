<?php
/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\InstallBundle\Form\Step;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Intl\Intl;

class SystemInformationForm extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $currencies = Intl::getCurrencyBundle()->getCurrencyNames();
        $locales = Intl::getLocaleBundle()->getLocaleNames();

        $builder->add(
            'locale',
            'choice',
            array(
                'choices' => $locales,
                'constraints' => array(
                    new Constraints\NotBlank(array('message' => 'Please select a locale'))
                ),
                'empty_value' => '',
                'attr' => array(
                    'class' => 'chosen'
                )
            )
        );

        $builder->add(
            'currency',
            'choice',
            array(
                'choices' => $currencies,
                'constraints' => array(
                    new Constraints\NotBlank(array('message' => 'Please select a currency'))
                ),
                'empty_value' => '',
                'attr' => array(
                    'class' => 'chosen'
                )
            )
        );

        /*$builder->add(
            'logo',
            'image_upload'
        );*/

        $builder->add(
            'username',
            null,
            array(
                'constraints' => array(
                    new Constraints\NotBlank(array('message' => 'Please enter a username'))
                )
            )
        );

        $builder->add(
            'email_address',
            'email',
            array(
                'constraints' => array(
                    new Constraints\NotBlank(array('message' => 'Please enter a email')),
                    new Constraints\Email(),
                )
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
                'first_options'  => array('label' => 'Password'),
                'second_options' => array('label' => 'Repeat Password'),
                'constraints' => array(
                    new Constraints\NotBlank(array('message' => 'You must enter a secure password')),
                    new Constraints\Length(array('min' => 6)),
                )
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
