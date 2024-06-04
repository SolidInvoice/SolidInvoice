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

use SolidInvoice\NotificationBundle\Entity\UserNotification;
use SolidInvoice\NotificationBundle\Repository\TransportSettingRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class NotificationSettingType extends AbstractType
{
    public const EMAIL_NOTIFICATION = 'fb4b16ae-6b76-4124-a706-0cb1419c780a';

    public function __construct(
        private readonly TransportSettingRepository $transportSettingRepository,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $integrations = ['Email' => self::EMAIL_NOTIFICATION];

        foreach ($this->transportSettingRepository->findAll() as $setting) {
            $integrations[$setting->getName()] = $setting->getId()->toString();
        }

        $builder->add(
            'event',
            HiddenType::class,
            [
                'data' => $options['event'],
            ]
        );

        $builder->add(
            'transports',
            ChoiceType::class,
            [
                'label' => 'Integrations',
                'choices' => $integrations,
                'multiple' => true,
                'expanded' => true,
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        //$resolver->setDefault('data_class', UserNotification::class);
        $resolver->setRequired('event');
        $resolver->setAllowedTypes('event', 'string');
    }
}
