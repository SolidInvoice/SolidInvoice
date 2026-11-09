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

namespace SolidInvoice\NotificationBundle\Enum;

enum NotificationCategory: string
{
    case CLIENT = 'client';
    case INVOICE = 'invoice';
    case PAYMENT = 'payment';
    case QUOTE = 'quote';
    case OTHER = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::CLIENT => 'notifications.categories.client',
            self::INVOICE => 'notifications.categories.invoice',
            self::PAYMENT => 'notifications.categories.payment',
            self::QUOTE => 'notifications.categories.quote',
            self::OTHER => 'notifications.categories.other',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::CLIENT => 'tabler:users',
            self::INVOICE => 'tabler:file-invoice',
            self::PAYMENT => 'tabler:credit-card',
            self::QUOTE => 'tabler:file-text',
            self::OTHER => 'tabler:bell',
        };
    }
}
