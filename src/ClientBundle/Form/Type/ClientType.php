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

namespace CSBill\ClientBundle\Form\Type;

use CSBill\ClientBundle\Entity\Client;
use CSBill\MoneyBundle\Form\Type\CurrencyType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ClientType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name');
        $builder->add('website', UrlType::class, ['required' => false]);

        $builder->add(
            'currency',
            CurrencyType::class,
            [
                'placeholder' => 'client.form.currency.empty_value',
                'required' => false,
            ]
        );

        $builder->add(
            'contacts',
            ContactCollectionType::class,
            [
                'entry_type' => ContactType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'delete_empty' => true,
                'by_reference' => false,
                'prototype' => true,
                'prototype_name' => '__contact_prototype__',
            ]
        );

        $builder->add(
            'addresses',
            CollectionType::class,
            [
                'entry_type' => AddressType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'delete_empty' => true,
                'by_reference' => false,
                'required' => false,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(['data_class' => Client::class]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'client';
    }
}
