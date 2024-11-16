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

use SolidInvoice\SettingsBundle\Form\Type\MailTransportType;
use SolidInvoice\SettingsBundle\SolidInvoiceSettingsBundle;
use SolidInvoice\SettingsBundle\SystemConfig;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\env;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services
        ->defaults()
        ->autoconfigure()
        ->autowire()
        ->private()
        ->bind('$installed', env('SOLIDINVOICE_INSTALLED'))
    ;

    $services
        ->load(SolidInvoiceSettingsBundle::NAMESPACE . '\\', dirname(__DIR__, 3))
        ->exclude(dirname(__DIR__, 3) . '/{DependencyInjection,Entity,Resources,Tests}');

    $services
        ->load(SolidInvoiceSettingsBundle::NAMESPACE . '\\Action\\', dirname(__DIR__, 3) . '/Action')
        ->tag('controller.service_arguments');

    $services
        ->get(MailTransportType::class)
        ->arg('$transports', tagged_iterator('solidinvoice_mailer.transport.configurator'));

    $services
        ->get(SystemConfig::class)
        ->public();
};
