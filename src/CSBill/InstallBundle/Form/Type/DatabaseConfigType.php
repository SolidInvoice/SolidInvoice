<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\InstallBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;

class DatabaseConfigType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $drivers = $options['drivers'];

        $builder->add(
            'driver',
            'select2',
            array(
                'help' => 'Only MySQL is supported at the moment',
                'choices' => $drivers,
                'placeholder' => 'Select Database Driver',
                'choices_as_values' => false,
                'constraints' => array(
                     new Constraints\NotBlank(),
                ),
            )
        );

        $builder->add(
            'host',
            null,
            array(
                'constraints' => array(
                    new Constraints\NotBlank(),
                ),
            )
        );

        $builder->add(
            'port',
            'integer',
            array(
                'constraints' => array(
                    new Constraints\Type(array('type' => 'integer')),
                ),
                'required' => false,
            )
        );

        $builder->add(
            'user',
            null,
            array(
                'constraints' => array(
                    new Constraints\NotBlank(),
                ),
            )
        );

        $builder->add(
            'password',
            'password',
            array(
                'required' => false,
            )
        );

        $builder->add(
            'name',
            null,
            array(
                'label' => 'Database Name',
                'constraints' => array(
                    new Constraints\NotBlank(),
                ),
                'required' => false,
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(
            array(
                'drivers',
            )
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'database_config';
    }
}
