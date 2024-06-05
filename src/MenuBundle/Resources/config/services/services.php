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

use SolidInvoice\MenuBundle\Factory;
use SolidInvoice\MenuBundle\Provider;
use SolidInvoice\MenuBundle\Renderer;
use SolidInvoice\MenuBundle\SolidInvoiceMenuBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services
        ->defaults()
        ->autoconfigure()
        ->autowire()
        ->private();

    $services
        ->load(SolidInvoiceMenuBundle::NAMESPACE . '\\', dirname(__DIR__, 3))
        ->exclude(dirname(__DIR__, 3) . '/{DependencyInjection,Entity,Resources,Tests}');

    $services
        ->set(Renderer::class)
        ->tag('knp_menu.renderer', ['alias' => 'solidinvoice']);

    $services
        ->set(Provider::class)
        ->args([service(Factory::class)])
        ->tag('knp_menu.provider');
};
