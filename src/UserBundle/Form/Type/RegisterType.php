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

use SolidInvoice\UserBundle\DTO\Registration;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\PasswordStrength;

final class RegisterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $emailOptions = [
            'required' => true,
            'constraints' => [
                new NotBlank(),
                new Email(['mode' => Email::VALIDATION_MODE_STRICT]),
            ],
        ];

        if (isset($options['email'])) {
            $emailOptions['data'] = $options['email'];
            $emailOptions['attr'] = [
                'readonly' => true,
            ];
        }

        $builder->add('firstName');
        $builder->add('lastName');

        $builder->add('email', EmailType::class, $emailOptions);
        $builder->add('company', null, [
            'required' => true,
            'label' => 'Company Name',
            'constraints' => new NotBlank(),
        ]);
        $builder->add(
            'plainPassword',
            RepeatedType::class,
            [
                'required' => true,
                'type' => PasswordType::class,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a password',
                    ]),
                    new Length([
                        'min' => 8,
                        'minMessage' => 'Your password should be at least {{ limit }} characters',
                        // max length allowed by Symfony for security reasons
                        'max' => 4096,
                    ]),
                    new PasswordStrength(minScore: PasswordStrength::STRENGTH_WEAK),
                ],
                'first_options' => ['label' => 'Password'],
                'second_options' => ['label' => 'Repeat Password'],
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('data_class', Registration::class);
        $resolver->setDefined('email');
        $resolver->setAllowedTypes('email', ['string']);
    }
}
