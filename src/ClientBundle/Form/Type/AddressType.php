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

use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @see \SolidInvoice\ClientBundle\Tests\Form\Type\AddressTypeTest
 * @extends AbstractType<array{street1: mixed, street2: mixed, city: mixed, state: mixed, zip: mixed, country: mixed}>
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

    #[Override]
    public function getBlockPrefix(): string
    {
        return 'address';
    }
}
