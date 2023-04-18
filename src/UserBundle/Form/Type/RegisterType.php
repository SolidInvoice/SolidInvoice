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

namespace SolidInvoice\UserBundle\Form\Type;

use SolidInvoice\UserBundle\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegisterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $emailOptions = [
            'required' => true,
            'constraints' => [new NotBlank(), new Email(['mode' => Email::VALIDATION_MODE_STRICT])],
        ];

        if (isset($options['email'])) {
            $emailOptions['data'] = $options['email'];
            $emailOptions['attr'] = [
                'readonly' => true
            ];
        }

        $builder->add('email', EmailType::class, $emailOptions);
        $builder->add(
            'plainPassword',
            RepeatedType::class,
            [
                'required' => true,
                'type' => PasswordType::class,
                'constraints' => new NotBlank(),
                'first_options' => ['label' => 'Password'],
                'second_options' => ['label' => 'Repeat Password'],
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('data_class', User::class);
        $resolver->setDefined('email');
        $resolver->setAllowedTypes('email', ['string']);
    }
}
