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

namespace SolidInvoice\PaymentBundle\Gateway;

/**
 * Presentation grouping for a payment gateway on the setup/marketplace screen.
 */
enum GatewayCategory: string
{
    /**
     * Hosted/online processors that charge the customer's card automatically
     * (Stripe, PayPal, …).
     */
    case Online = 'online';

    /**
     * Manual methods where the payment happens outside SolidInvoice and is only
     * recorded here (cash, bank transfer, …).
     */
    case Offline = 'offline';

    /**
     * A user-defined method that does not map to a known processor.
     */
    case Custom = 'custom';

    public function getLabel(): string
    {
        return match ($this) {
            self::Online => 'payment.gateway.section.online.title',
            self::Offline => 'payment.gateway.section.offline.title',
            self::Custom => 'payment.gateway.section.offline.title',
        };
    }
}
