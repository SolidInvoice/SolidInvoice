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

namespace SolidInvoice\UserBundle\Onboarding\Form\Step;

use Money\Currency;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class InvoiceSetupStep extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('invoiceDescription', TextareaType::class, [
            'label' => 'Service Description',
            'required' => false,
            'attr' => [
                'placeholder' => 'Website Design',
                'rows' => 3,
            ],
        ]);

        $builder->add('invoiceAmount', MoneyType::class, [
            'label' => 'Amount',
            'required' => false,
            'currency' => new Currency($options['currency']),
            'attr' => [
                'placeholder' => '1000.00',
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'validation_groups' => ['invoice'],
            'currency' => 'USD',
        ]);

        $resolver->setAllowedTypes('currency', 'string');
    }
}
