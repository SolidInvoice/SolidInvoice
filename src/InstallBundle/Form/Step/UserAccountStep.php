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

use SolidInvoice\InstallBundle\DTO\UserAccount;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Intl\Locales;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @see \SolidInvoice\InstallBundle\Tests\Form\Step\SystemInformationFormTest
 */
class UserAccountStep extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (extension_loaded('intl')) {
            $builder->add(
                'locale',
                ChoiceType::class,
                [
                    'choices' => array_flip(Locales::getNames()),
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

        $builder->add('firstName');
        $builder->add('lastName');

        $builder->add('emailAddress', EmailType::class);

        $builder->add(
            'password',
            PasswordType::class,
            [
                'attr' => ['class' => 'password-field'],
                'required' => true,
                'always_empty' => false,
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UserAccount::class,
        ]);
    }
}
