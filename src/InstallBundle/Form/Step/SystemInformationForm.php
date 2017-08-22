<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\InstallBundle\Form\Step;

use SolidInvoice\CoreBundle\Form\Type\Select2Type;
use SolidInvoice\MoneyBundle\Form\Type\CurrencyType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Intl\Intl;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;

class SystemInformationForm extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (extension_loaded('intl')) {
            $builder->add(
                'locale',
                Select2Type::class,
                [
                    'choices' => array_flip(Intl::getLocaleBundle()->getLocaleNames()),
                    'constraints' => new Constraints\NotBlank(['message' => 'Please select a locale']),
                    'placeholder' => 'Please select a locale',
                ]
            );
        } else {
            $builder->add(
                'locale',
                null,
                [
                    'data' => 'en',
                    'read_only' => true,
                    'help' => 'The only currently supported locale is "en". To choose a different locale, please install the \'intl\' extension',
                    'placeholder' => 'Please select a locale',
                ]
            );
        }

        $builder->add(
            'currency',
            CurrencyType::class,
            [
                'constraints' => new Constraints\NotBlank(['message' => 'Please select a currency']),
                'placeholder' => 'Please select a currency',
            ]
        );

        $builder->add(
            'base_url',
            null,
            [
                'constraints' => new Constraints\NotBlank(['message' => 'Please set the application base url']),
            ]
        );

        if (0 === $options['userCount']) {
            $builder->add(
                'username',
                null,
                [
                    'constraints' => new Constraints\NotBlank(['message' => 'Please enter a username']),
                ]
            );

            $builder->add(
                'email_address',
                EmailType::class,
                [
                    'constraints' => [
                        new Constraints\NotBlank(['message' => 'Please enter a email']),
                        new Constraints\Email(),
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
                        new Constraints\NotBlank(['message' => 'You must enter a secure password']),
                        new Constraints\Length(['min' => 6]),
                    ],
                ]
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
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
