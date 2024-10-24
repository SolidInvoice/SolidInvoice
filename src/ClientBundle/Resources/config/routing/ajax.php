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

use SolidInvoice\ClientBundle\Action\Ajax\Credit;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routingConfigurator): void {
    $routingConfigurator
        ->add('_xhr_clients_credit_get', '/credit/{client}')
        ->controller([Credit::class, 'get'])
        ->methods(['GET']);

    $routingConfigurator
        ->add('_xhr_clients_credit_update', '/credit/{client}')
        ->controller([Credit::class, 'put'])
        ->methods(['PUT']);

    $routingConfigurator
        ->add('_xhr_clients_delete', '/delete/{id}')
        ->controller(\SolidInvoice\ClientBundle\Action\Ajax\Delete::class)
        ->methods(['DELETE']);
};
