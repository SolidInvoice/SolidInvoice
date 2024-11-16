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

use Gedmo\Timestampable\TimestampableListener;
use Mpociot\VatCalculator\VatCalculator;
use SolidInvoice\CoreBundle\Menu\Builder;
use SolidInvoice\CoreBundle\Routing\Loader\AbstractDirectoryLoader;
use SolidInvoice\CoreBundle\SolidInvoiceCoreBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Uid\Command\GenerateUlidCommand;
use Symfony\Component\Uid\Command\GenerateUuidCommand;
use Symfony\Component\Uid\Command\InspectUlidCommand;
use Symfony\Component\Uid\Command\InspectUuidCommand;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;
use function Symfony\Component\DependencyInjection\Loader\Configurator\env;
use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services
        ->defaults()
        ->autowire()
        ->autoconfigure()
        ->private()
        ->bind('$projectDir', param('kernel.project_dir'))
        ->bind('$cacheDir', param('kernel.cache_dir'))
        ->bind('$installed', env('SOLIDINVOICE_INSTALLED'))
        ->bind('$vault', service('secrets.vault'))
    ;

    $services
        ->load(SolidInvoiceCoreBundle::NAMESPACE . '\\', dirname(__DIR__, 3))
        ->exclude(dirname(__DIR__, 3) . '/{DependencyInjection,Entity,Resources,Tests}');

    $services
        ->load(SolidInvoiceCoreBundle::NAMESPACE . '\\Action\\', dirname(__DIR__, 3) . '/Action')
        ->autowire(true)
        ->tag('controller.service_arguments');

    $services
        ->set(TimestampableListener::class)
        ->tag('doctrine.event_subscriber')
    ;

    $services->set(CssToInlineStyles::class);

    $services
        ->set(Builder::class)
        ->tag('cs_core.menu', [
            'menu' => 'sidebar',
            'method' => 'systemMenu',
            'priority' => -200,
        ])
        ->tag('cs_core.menu', [
            'menu' => 'sidebar',
            'method' => 'userMenu',
            'priority' => -255,
        ]);

    $services
        ->set(AbstractDirectoryLoader::class)
        ->lazy()
        ->abstract()
        ->arg('$locator', service('file_locator'))
        ->arg('$kernel', service('kernel'));

    $services->set(VatCalculator::class);

    $services->set(GenerateUlidCommand::class);
    $services->set(GenerateUuidCommand::class);
    $services->set(InspectUlidCommand::class);
    $services->set(InspectUuidCommand::class);
};
