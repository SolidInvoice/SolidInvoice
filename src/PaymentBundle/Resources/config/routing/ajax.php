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

use SolidInvoice\PaymentBundle\Action\Ajax\MethodList;
use SolidInvoice\PaymentBundle\Action\Ajax\Settings;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routingConfigurator): void {
    $routingConfigurator->add('_xhr_payments_method_list', '/methods/list')
        ->controller(MethodList::class);

    $routingConfigurator->add('_xhr_payments_settings', '/settings/{method}')
        ->controller(Settings::class);
};
