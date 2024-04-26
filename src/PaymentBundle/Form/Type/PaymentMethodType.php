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

namespace SolidInvoice\PaymentBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @see \SolidInvoice\PaymentBundle\Tests\Form\Type\PaymentMethodTypeTest
 */
class PaymentMethodType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('name');

        $builder->add('enabled', CheckboxType::class, ['required' => false, 'label_attr' => ['class' => 'switch-custom']]);

        if (false === $options['internal']) {
            $builder->add(
                'internal',
                CheckboxType::class,
                [
                    'label_attr' => ['class' => 'switch-custom'],
                    'label' => 'payment.form.label.internal',
                    'help' => 'payment.form.help.internal',
                    'help_type' => 'block',
                    'required' => false,
                ]
            );
        }

        if (null !== $options['config']) {
            $builder->add('config', $options['config']);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(['config']);
        $resolver->setAllowedTypes('config', ['string', 'null']);

        $resolver->setDefaults(
            [
                'internal' => false,
                'validation_groups' => function (FormInterface $form): bool|string {
                    // If the method is disabled, don't use any constraints
                    if (false === $form->get('enabled')->getData()) {
                        return false;
                    }

                    // Otherwise, use the default validation group
                    return 'Default';
                },
            ]
        );
    }

    public function getBlockPrefix(): string
    {
        return 'payment_methods';
    }
}
