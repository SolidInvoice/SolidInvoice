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

use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\MoneyBundle\Form\Type\CurrencyType;
use SolidInvoice\TaxBundle\Form\Type\TaxNumberType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\LiveComponent\Form\Type\LiveCollectionType;

/**
 * @see \SolidInvoice\ClientBundle\Tests\Form\Type\ClientTypeTest
 */
class ClientType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('name');
        $builder->add('website', UrlType::class, ['required' => false]);

        $builder->add(
            'currencyCode',
            CurrencyType::class,
            [
                'placeholder' => 'client.form.currency.empty_value',
                'required' => false,
            ]
        );

        $builder->add('vat_number', TaxNumberType::class, ['required' => false]);

        $builder->add(
            'contacts',
            LiveCollectionType::class,
            [
                'entry_type' => ContactType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'button_delete_options' => [
                    'label_html' => true,
                ],
            ]
        );

        $builder->add(
            'addresses',
            LiveCollectionType::class,
            [
                'entry_type' => AddressType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'required' => false,
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Client::class]);
    }

    public function getBlockPrefix()
    {
        return 'client';
    }
}
