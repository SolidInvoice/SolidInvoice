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

use SolidInvoice\ProductBundle\Action\Add;
use SolidInvoice\ProductBundle\Action\Edit;
use SolidInvoice\ProductBundle\Action\Index;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routingConfigurator): void {
    $routingConfigurator
        ->add('_products', '/')
        ->controller(Index::class);

    $routingConfigurator
        ->add('_products_add', '/add')
        ->controller(Add::class);

    $routingConfigurator
        ->add('_products_edit', '/edit/{id}')
        ->controller(Edit::class);
};
