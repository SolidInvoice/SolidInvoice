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

use SolidInvoice\UserBundle\Action\Ajax\ApiCreate;
use SolidInvoice\UserBundle\Action\Ajax\ApiList;
use SolidInvoice\UserBundle\Action\Ajax\ApiRevoke;
use SolidInvoice\UserBundle\Action\Ajax\ApiTokenHistory;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routingConfigurator): void {
    $routingConfigurator
        ->add('_xhr_api_keys_list', '/list')
        ->controller(ApiList::class)
        ->methods(['GET']);

    $routingConfigurator
        ->add('_xhr_api_keys_create', '/create')
        ->controller(ApiCreate::class)
        ->methods(['GET', 'POST']);

    $routingConfigurator
        ->add('_xhr_api_keys_revoke', '/revoke/{id}')
        ->controller(ApiRevoke::class)
        ->methods(['DELETE']);

    $routingConfigurator
        ->add('_xhr_api_keys_history', '/history/{id}')
        ->controller(ApiTokenHistory::class)
        ->methods(['GET']);
};
