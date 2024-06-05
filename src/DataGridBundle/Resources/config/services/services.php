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

use SolidInvoice\CoreBundle\Routing\Loader\AbstractDirectoryLoader;
use SolidInvoice\DataGridBundle\Routing\Loader\GridRouteLoader;
use SolidInvoice\DataGridBundle\SolidInvoiceDataGridBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services
        ->defaults()
        ->autoconfigure()
        ->autowire()
        ->private()
    ;

    $services
        ->load(SolidInvoiceDataGridBundle::NAMESPACE . '\\', dirname(__DIR__, 3))
        ->exclude(dirname(__DIR__, 3) . '/{DependencyInjection,Entity,Resources,Tests}');

    $services
        ->load(SolidInvoiceDataGridBundle::NAMESPACE . '\\Action\\', dirname(__DIR__, 3) . '/Action')
        ->tag('controller.service_arguments');

    $services
        ->set(GridRouteLoader::class)
        ->parent(AbstractDirectoryLoader::class)
        ->tag('routing.loader');
};
