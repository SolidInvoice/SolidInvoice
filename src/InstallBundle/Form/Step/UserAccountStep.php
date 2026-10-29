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

use SolidInvoice\InstallBundle\DTO\Installation;
use SolidInvoice\InstallBundle\DTO\UserAccount;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Intl\Locales;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Url;

/**
 * @see \SolidInvoice\InstallBundle\Tests\Form\Step\SystemInformationFormTest
 * @extends AbstractType<UserAccount>
 */
class UserAccountStep extends AbstractType
{
    public function __construct(
        private readonly RequestStack $requestStack,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'applicationUrl',
            UrlType::class,
            [
                'mapped' => false,
                'required' => true,
                'default_protocol' => null,
                'label' => 'installation.user_account.application_url',
                'help' => 'installation.user_account.application_url_help',
                'constraints' => [
                    new NotBlank(),
                    // requireTld defaults to true in Symfony 8, but installing on
                    // http://localhost or a plain IP address must remain possible.
                    new Url(protocols: ['http', 'https'], requireTld: false),
                ],
            ],
        );

        $builder->get('applicationUrl')
            ->addEventListener(
                FormEvents::PRE_SET_DATA,
                function (FormEvent $event): void {
                    if ($event->getData() !== null && $event->getData() !== '') {
                        return;
                    }

                    $root = $event->getForm()
                        ->getRoot()
                        ->getData();

                    if ($root instanceof Installation && $root->applicationUrl !== null && $root->applicationUrl !== '') {
                        $event->setData($root->applicationUrl);
                        return;
                    }

                    $event->setData($this->requestStack->getCurrentRequest()?->getSchemeAndHttpHost());
                },
            );

        $builder->add(
            'telemetryEnabled',
            CheckboxType::class,
            [
                'mapped' => false,
                'required' => false,
                'data' => true,
                'label' => 'installation.user_account.telemetry',
                'help' => 'installation.user_account.telemetry_help',
            ],
        );

        $builder->get('telemetryEnabled')
            ->addEventListener(
                FormEvents::PRE_SET_DATA,
                static function (FormEvent $event): void {
                    $root = $event->getForm()
                        ->getRoot()
                        ->getData();

                    if ($root instanceof Installation) {
                        $event->setData($root->telemetryEnabled);
                    }
                },
            );

        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            static function (FormEvent $event): void {
                $root = $event->getForm()
                    ->getRoot()
                    ->getData();

                if ($root instanceof Installation) {
                    $root->applicationUrl = $event->getForm()
                        ->get('applicationUrl')
                        ->getData();
                    $root->telemetryEnabled = (bool) $event->getForm()
                        ->get('telemetryEnabled')
                        ->getData();
                }
            },
        );

        if (extension_loaded('intl')) {
            $builder->add(
                'locale',
                ChoiceType::class,
                [
                    'label' => 'installation.user_account.locale_label',
                    'choices' => array_flip(Locales::getNames()),
                    'choice_translation_domain' => false,
                    'placeholder' => 'installation.user_account.locale_placeholder',
                ]
            );
        } else {
            $builder->add(
                'locale',
                null,
                [
                    'label' => 'installation.user_account.locale_label',
                    'data' => 'en',
                    'attr' => [
                        'readonly' => true,
                    ],
                    'help' => 'installation.user_account.locale_help',
                    'placeholder' => 'installation.user_account.locale_placeholder',
                ]
            );
        }

        $builder->add('firstName', null, ['label' => 'installation.user_account.first_name']);
        $builder->add('lastName', null, ['label' => 'installation.user_account.last_name']);

        $builder->add('emailAddress', EmailType::class, ['label' => 'installation.user_account.email_address']);

        $builder->add(
            'password',
            PasswordType::class,
            [
                'label' => 'installation.user_account.password',
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
