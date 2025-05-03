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

use SolidInvoice\QuoteBundle\Menu\Builder;
use SolidInvoice\QuoteBundle\SolidInvoiceQuoteBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services
        ->defaults()
        ->autoconfigure()
        ->autowire()
        ->private()
        ->bind('$invoiceStateMachine', service('state_machine.invoice'))
        ->bind('$quoteStateMachine', service('state_machine.quote'))
    ;

    $services
        ->load(SolidInvoiceQuoteBundle::NAMESPACE . '\\', dirname(__DIR__, 3))
        ->exclude(dirname(__DIR__, 3) . '/{DependencyInjection,Entity,Resources,Tests}');

    $services
        ->load(SolidInvoiceQuoteBundle::NAMESPACE . '\\Action\\', dirname(__DIR__, 3) . '/Action')
        ->tag('controller.service_arguments');

    $services
        ->set(Builder::class)
        ->tag(
            'cs_core.menu',
            [
                'menu' => 'sidebar',
                'method' => 'sidebar',
            ]
        );
};
