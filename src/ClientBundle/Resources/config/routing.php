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

use SolidInvoice\ClientBundle\Action\Add;
use SolidInvoice\ClientBundle\Action\Edit;
use SolidInvoice\ClientBundle\Action\Index;
use SolidInvoice\ClientBundle\Action\View;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routingConfigurator): void {
    $routingConfigurator
        ->add('_clients_index', '/')
        ->controller(Index::class)
        ->options(['expose' => true]);

    $routingConfigurator
        ->add('_clients_add', '/add')
        ->controller(Add::class)
        ->options(['expose' => true]);

    $routingConfigurator
        ->add('_clients_edit', '/edit/{id}')
        ->controller(Edit::class)
        ->options(['expose' => true]);

    $routingConfigurator
        ->add('_clients_view', '/view/{id}')
        ->controller(View::class)
        ->options(['expose' => true]);

    $routingConfigurator->import('@SolidInvoiceClientBundle/Resources/config/routing/ajax.php')
        ->prefix('/xhr')
        ->options(['expose' => true]);
};
