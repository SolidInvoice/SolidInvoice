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

namespace SolidInvoice\ClientBundle\Form\Type;

use SolidInvoice\ClientBundle\Entity\Address;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @see \SolidInvoice\ClientBundle\Tests\Form\Type\AddressTypeTest
 */
class AddressType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('street1');
        $builder->add('street2');
        $builder->add('city');
        $builder->add('state');
        $builder->add('zip');
        $builder->add(
            'country',
            CountryType::class,
            [
                'placeholder' => 'client.address.country.select',
                'required' => false,
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Address::class]);
    }

    public function getBlockPrefix(): string
    {
        return 'address';
    }
}
