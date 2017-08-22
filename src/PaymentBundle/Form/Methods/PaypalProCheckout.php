<?php

declare(strict_types = 1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\PaymentBundle\Form\Methods;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class PaypalProCheckout extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'username',
            TextType::class,
            [
                'constraints' => new NotBlank(),
            ]
        );

        $builder->add(
            'password',
            PasswordType::class,
            [
                //'help' => 'payment.settings.password.hint',
                'constraints' => new NotBlank(), // @TODO: This constraint should only be added when saving for the first time
            ]
        );

        $builder->add(
            'partner',
            TextType::class,
            [
                'constraints' => new NotBlank(),
            ]
        );

        $builder->add(
            'vendor',
            TextType::class,
            [
                'constraints' => new NotBlank(),
            ]
        );

        $builder->add(
            'sandbox',
            CheckboxType::class,
            [
                'required' => false,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'paypal_pro_checkout';
    }
}
