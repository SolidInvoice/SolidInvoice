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

use SolidInvoice\PaymentBundle\Action\Done;
use SolidInvoice\PaymentBundle\Action\Index;
use SolidInvoice\PaymentBundle\Action\Prepare;
use SolidInvoice\PaymentBundle\Action\Settings;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routingConfigurator): void {
    $routingConfigurator
        ->add('_payments_index', '/')
        ->controller(Index::class);

    $routingConfigurator
        ->add('_payment_settings_index', '/methods')
        ->controller(Settings::class)
        ->options(['expose' => true]);

    $routingConfigurator
        ->add('_payments_create', '/create/{uuid}')
        ->controller(Prepare::class)
        /*->requirements(['uuid' => '[a-zA-Z0-9-]{36}'])*/;

    $routingConfigurator
        ->add('_payments_done', '/done')
        ->controller(Done::class);
};
