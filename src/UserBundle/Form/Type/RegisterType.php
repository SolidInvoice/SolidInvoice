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
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

final class RegisterType extends AbstractType
{
    public function __construct(
        private readonly TranslatorInterface $translator
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $emailOptions = [
            'required' => true,
            'attr' => [
                'placeholder' => $this->translator->trans('security.register.placeholders.email'),
                'autocomplete' => 'email',
                'autofocus' => true,
            ],
        ];

        if (isset($options['email'])) {
            $emailOptions['data'] = $options['email'];
            $emailOptions['attr'] = [
                'readonly' => true,
            ];
        }

        $builder->add('email', EmailType::class, $emailOptions);
        $builder->add('plainPassword', PasswordType::class, [
            'required' => true,
            'label' => 'Password',
            'use_toggle_form_theme' => false,
            'toggle' => false,
            'attr' => [
                'placeholder' => 'Create a strong password',
                'autocomplete' => 'new-password',
            ],
        ]);
        $builder->add('acceptTerms', CheckboxType::class, [
            'required' => true,
            'label' => 'I agree to the  <a href="https://solidinvoice.co/terms-of-service" target="_blank" class="link-primary" rel="external noreferrer noopener">Terms & Conditions</a> and <a href="https://solidinvoice.co/privacy-policy" target="_blank" class="link-primary" rel="external noreferrer noopener">Privacy Policy</a>',
            'label_html' => true,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('data_class', Registration::class);
        $resolver->setDefined('email');
        $resolver->setAllowedTypes('email', ['string']);
    }
}
