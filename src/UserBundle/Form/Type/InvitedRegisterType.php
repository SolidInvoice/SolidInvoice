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

use SolidInvoice\UserBundle\DTO\InvitedRegistration;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

final class InvitedRegisterType extends AbstractType
{
    public function __construct(
        private readonly TranslatorInterface $translator
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('email', EmailType::class, [
            'required' => true,
            'data' => $options['email'],
            'attr' => [
                'readonly' => true,
                'autocomplete' => 'email',
            ],
        ]);

        $builder->add('firstName', TextType::class, [
            'required' => true,
            'label' => $this->translator->trans('security.register.invited.first_name'),
            'attr' => [
                'placeholder' => $this->translator->trans('security.register.placeholders.first_name'),
                'autocomplete' => 'given-name',
            ],
        ]);

        $builder->add('lastName', TextType::class, [
            'required' => true,
            'label' => $this->translator->trans('security.register.invited.last_name'),
            'attr' => [
                'placeholder' => $this->translator->trans('security.register.placeholders.last_name'),
                'autocomplete' => 'family-name',
            ],
        ]);

        $builder->add('plainPassword', PasswordType::class, [
            'required' => true,
            'label' => 'Password',
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
        $resolver->setDefault('data_class', InvitedRegistration::class);
        $resolver->setRequired('email');
        $resolver->setAllowedTypes('email', ['string']);
    }
}
