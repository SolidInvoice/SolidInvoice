<?php

/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\ClientBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use CSBill\ClientBundle\Form\Type\ContactType;

class Client extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name');
        $builder->add('website');
        $builder->add('contacts', new ContactType(), array(
                                                    'type' => 'contact',
                                                    'allow_add' => true,
                                                    'allow_delete' => true,
                                                    'by_reference' => false,
                                                    'prototype' => true,
                                                    'prototype_name' => '__contact_prototype__'
                                                    ));
    }

    public function getName()
    {
        return 'client';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
                'data_class' => 'CSBill\ClientBundle\Entity\Client'
        ));
    }
}
