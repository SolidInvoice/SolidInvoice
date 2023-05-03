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

use Payum\Core\Registry\RegistryInterface;
use SolidInvoice\CoreBundle\Billing\TotalCalculator;
use SolidInvoice\PaymentBundle\Menu\Builder;
use SolidInvoice\PaymentBundle\PaymentAction\Offline\StatusAction;
use SolidInvoice\PaymentBundle\PaymentAction\PaypalExpress\PaymentDetailsStatusAction;
use SolidInvoice\PaymentBundle\Payum\Extension\UpdatePaymentDetailsExtension;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\ServiceLocator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->defaults()
        ->autoconfigure()
        ->autowire()
        ->private()
        ->bind('$invoiceStateMachine', service('state_machine.invoice'))
    ;

    $services->load('SolidInvoice\\PaymentBundle\\', dirname(__DIR__, 3))
        ->exclude(dirname(__DIR__, 3) . '/{DependencyInjection,Resources,Tests}');

    $services->load('SolidInvoice\\PaymentBundle\\Action\\', dirname(__DIR__, 3) . '/Action')
        ->tag('controller.service_arguments');

    $services->set(Builder::class)
        ->tag('cs_core.menu', [
            'menu' => 'sidebar',
            'method' => 'mainMenu',
            'priority' => -1,
        ]);

    $services->set(PaymentDetailsStatusAction::class)
        ->public()
        ->tag('payum.action', ['factory' => 'paypal_express_checkout', 'prepend' => true]);

    $services->set(StatusAction::class)
        ->public()
        ->tag('payum.action', ['factory' => 'offline']);

    $services->set(UpdatePaymentDetailsExtension::class)
        ->public()
        ->tag('payum.extension', ['all' => true]);

    $services->set('solidinvoice.payment.locator', ServiceLocator::class)
        ->tag('container.service_locator')
        ->args([
            [
                TotalCalculator::class => service(TotalCalculator::class),
            ],
        ]);

    $services->load('SolidInvoice\\PaymentBundle\\DataFixtures\ORM\\', dirname(__DIR__, 3) . '/DataFixtures/ORM/*')
        ->tag('doctrine.fixture.orm');

    $services->alias(RegistryInterface::class, 'payum');
};
