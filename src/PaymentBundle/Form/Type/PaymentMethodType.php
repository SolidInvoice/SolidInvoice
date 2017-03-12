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

namespace CSBill\PaymentBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PaymentMethodType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name');

        $builder->add('enabled', null, ['required' => false]);

        if (false === $options['internal']) {
            $builder->add(
                'internal',
                null,
                [
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

        $builder->add(
            'save',
            SubmitType::class,
            [
                'attr' => [
                    'class' => 'btn-success',
                ],
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['config']);
        $resolver->setAllowedTypes('config', ['string', 'null']);

        $resolver->setDefaults(
            [
                'internal' => false,
                'validation_groups' => function (FormInterface $form) {
                    // If the method is disabled, don't use any constraints
                    if ($form->get('enabled')->getData() === false) {
                        return false;
                    }

                    // Otherwise, use the default validation group
                    return 'Default';
                },
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'payment_methods';
    }
}
