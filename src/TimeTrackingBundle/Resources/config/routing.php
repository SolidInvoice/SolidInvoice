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

use SolidInvoice\TimeTrackingBundle\Action\CreateTimeEntry;
use SolidInvoice\TimeTrackingBundle\Action\EditTimeEntry;
use SolidInvoice\TimeTrackingBundle\Action\GenerateInvoice;
use SolidInvoice\TimeTrackingBundle\Action\Index;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routingConfigurator): void {
    $routingConfigurator
        ->add('_time_tracking_index', '/')
        ->controller(Index::class);

    $routingConfigurator
        ->add('_time_tracking_entry_create', '/log')
        ->controller(CreateTimeEntry::class);

    $routingConfigurator
        ->add('_time_tracking_entry_edit', '/log/{id}/edit')
        ->controller(EditTimeEntry::class);

    $routingConfigurator
        ->add('_time_tracking_generate_invoice', '/generate-invoice')
        ->controller(GenerateInvoice::class)
        ->methods(['POST']);
};
