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
use SolidInvoice\CoreBundle\Listener\SessionRequestListener;
use SolidInvoice\CoreBundle\Menu\Builder;
use SolidInvoice\CoreBundle\Routing\Loader\AbstractDirectoryLoader;
use SolidInvoice\CoreBundle\SolidInvoiceCoreBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
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
        ->bind('$installed', env('installed'))
    ;

    $services
        ->load(SolidInvoiceCoreBundle::NAMESPACE . '\\', dirname(__DIR__, 3))
        ->exclude(dirname(__DIR__, 3) . '/{DependencyInjection,Resources,Tests}');

    $services
        ->load(SolidInvoiceCoreBundle::NAMESPACE . '\\Action\\', dirname(__DIR__, 3) . '/Action')
        ->autowire(true)
        ->tag('controller.service_arguments');

    $services
        ->set(TimestampableListener::class)
        ->call('setAnnotationReader', [service('annotation_reader')]);

    $services
        ->set(SessionRequestListener::class)
        ->arg('$secret', env('secret'));

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
};
