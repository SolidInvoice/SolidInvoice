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

namespace SolidInvoice\InstallBundle\Form\Step;

use SolidInvoice\CoreBundle\Form\Type\Select2Type;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Intl\Locales;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @see \SolidInvoice\InstallBundle\Tests\Form\Step\SystemInformationFormTest
 */
class SystemInformationForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (extension_loaded('intl')) {
            $builder->add(
                'locale',
                Select2Type::class,
                [
                    'choices' => array_flip(Locales::getNames()),
                    'constraints' => new NotBlank(['message' => 'Please select a locale']),
                    'placeholder' => 'Please select a locale',
                ]
            );
        } else {
            $builder->add(
                'locale',
                null,
                [
                    'data' => 'en',
                    'attr' => [
                        'readonly' => true,
                    ],
                    'help' => 'The only currently supported locale is "en". To choose a different locale, please install the \'intl\' extension',
                    'placeholder' => 'Please select a locale',
                ]
            );
        }

        if (0 === $options['userCount']) {
            $builder->add(
                'email_address',
                EmailType::class,
                [
                    'constraints' => [
                        new NotBlank(['message' => 'Please enter a email']),
                        new Email(),
                    ],
                ]
            );

            $builder->add(
                'password',
                RepeatedType::class,
                [
                    'type' => PasswordType::class,
                    'invalid_message' => 'The password fields must match.',
                    'options' => ['attr' => ['class' => 'password-field']],
                    'required' => true,
                    'first_options' => ['label' => 'Password'],
                    'second_options' => ['label' => 'Repeat Password'],
                    'constraints' => [
                        new NotBlank(['message' => 'You must enter a secure password']),
                        new Length(['min' => 6]),
                    ],
                ]
            );
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('userCount');
        $resolver->setAllowedTypes('userCount', ['int']);
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'system_information';
    }
}
