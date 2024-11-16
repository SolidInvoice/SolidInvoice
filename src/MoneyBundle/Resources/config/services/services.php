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

use SolidInvoice\MoneyBundle\Formatter\MoneyFormatter;
use SolidInvoice\MoneyBundle\Formatter\MoneyFormatterInterface;
use SolidInvoice\MoneyBundle\SolidInvoiceMoneyBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\env;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services
        ->defaults()
        ->autoconfigure()
        ->autowire()
        ->private()
        ->bind('$locale', env('SOLIDINVOICE_LOCALE'))
        ->bind('$normalizer', service('api_platform.serializer.normalizer.item'))
    ;

    $services
        ->load(SolidInvoiceMoneyBundle::NAMESPACE . '\\', dirname(__DIR__, 3))
        ->exclude(dirname(__DIR__, 3) . '/{DependencyInjection,Entity,Resources,Tests}');

    $services->alias(MoneyFormatterInterface::class, MoneyFormatter::class);
};
