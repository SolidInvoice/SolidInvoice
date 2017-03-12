<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\ClientBundle\Form\Type;

use CSBill\ClientBundle\Entity\AdditionalContactDetail;
use CSBill\ClientBundle\Entity\Contact;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContactType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('firstName');
        $builder->add('lastName');
        $builder->add('email');

        $builder->add(
            'additionalDetails',
            ContactDetailCollectionType::class,
            [
                'entry_type' => ContactDetailType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'delete_empty' => true,
                'required' => false,
                'by_reference' => false,
                'label' => 'contact_details',
                'prototype' => true,
                'prototype_name' => '__contact_details_prototype__',
                'error_bubbling' => false,
                'entry_options' => [
                    'data_class' => AdditionalContactDetail::class,
                ],
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['allow_delete'] = $options['allow_delete'];
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => Contact::class,
                'csrf_protection' => false,
                'allow_delete' => true,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'contact';
    }
}
