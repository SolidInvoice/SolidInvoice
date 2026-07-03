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

namespace SolidInvoice\PaymentBundle\Form\Methods;

use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @extends AbstractType<array{username: string, password: string, signature: string, sandbox: bool}>
 */
class PaypalExpressCheckout extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'username',
            TextType::class,
            [
                'label' => 'payment.gateway.field.paypal.username.label',
                'help' => 'payment.gateway.field.paypal.username.help',
                'constraints' => new NotBlank(),
            ]
        );

        $builder->add(
            'password',
            PasswordType::class,
            [
                'label' => 'payment.gateway.field.paypal.password.label',
                'help' => 'payment.gateway.field.paypal.password.help',
                'constraints' => new NotBlank(),
                'always_empty' => false,
            ]
        );

        $builder->add(
            'signature',
            TextType::class,
            [
                'label' => 'payment.gateway.field.paypal.signature.label',
                'help' => 'payment.gateway.field.paypal.signature.help',
                'constraints' => new NotBlank(),
            ]
        );

        $builder->add(
            'sandbox',
            CheckboxType::class,
            [
                'label' => 'payment.gateway.field.sandbox.label',
                'help' => 'payment.gateway.field.sandbox.help',
                'label_attr' => ['class' => 'switch-custom'],
                'required' => false,
            ]
        );
    }

    #[Override]
    public function getBlockPrefix(): string
    {
        return 'paypal_express_checkout';
    }
}
