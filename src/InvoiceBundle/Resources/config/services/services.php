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

use SolidInvoice\InvoiceBundle\Form\EventListener\InvoiceUsersSubscriber;
use SolidInvoice\InvoiceBundle\Menu\Builder;
use SolidInvoice\InvoiceBundle\SolidInvoiceInvoiceBundle;
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
        ->bind('$recurringInvoiceStateMachine', service('state_machine.recurring_invoice'))
    ;

    $services
        ->load(SolidInvoiceInvoiceBundle::NAMESPACE . '\\', dirname(__DIR__, 3))
        ->exclude(dirname(__DIR__, 3) . '/{DependencyInjection,Entity,Resources,Tests}');

    $services
        ->load(SolidInvoiceInvoiceBundle::NAMESPACE . '\\Action\\', dirname(__DIR__, 3) . '/Action')
        ->tag('controller.service_arguments');

    $services
        ->set('solidinvoice_invoice.menu', Builder::class)
        ->tag(
            'cs_core.menu',
            [
                'menu' => 'sidebar',
                'method' => 'sidebar',
            ]
        );

    $services
        ->load(SolidInvoiceInvoiceBundle::NAMESPACE . '\\DataFixtures\ORM\\', dirname(__DIR__, 3) . '/DataFixtures/ORM/*')
        ->tag('doctrine.fixture.orm');

    $services->remove(InvoiceUsersSubscriber::class);
};
