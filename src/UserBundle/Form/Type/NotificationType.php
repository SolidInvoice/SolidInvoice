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

use Symfony\Component\DependencyInjection\Attribute\TaggedLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

final class NotificationType extends AbstractType
{
    public function __construct(
        #[TaggedLocator('solid_invoice_notification.notification', 'name')]
        private readonly ServiceLocator $notificationList,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $notificationEvents = array_keys($this->notificationList->getProvidedServices());

        foreach ($notificationEvents as $event) {
            $builder->add(
                $event,
                NotificationSettingType::class,
                [
                    'event' => $event,
                ]
            );
        }
    }
}
