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

use Doctrine\Migrations\DependencyFactory;
use SolidInvoice\InstallBundle\SolidInvoiceInstallBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\DoctrineDbalStore;
use function Symfony\Component\DependencyInjection\Loader\Configurator\env;
use function Symfony\Component\DependencyInjection\Loader\Configurator\inline_service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services
        ->defaults()
        ->autoconfigure()
        ->autowire()
        ->private()
        ->bind('$installed', env('SOLIDINVOICE_INSTALLED'))
        ->bind('$debug', param('kernel.debug'))
        ->bind('$vault', service('secrets.vault'))
    ;

    $services
        ->load(SolidInvoiceInstallBundle::NAMESPACE . '\\', dirname(__DIR__, 3))
        ->exclude(dirname(__DIR__, 3) . '/{DependencyInjection,Entity,Resources,Tests}');

    $services
        ->load(SolidInvoiceInstallBundle::NAMESPACE . '\\Action\\', dirname(__DIR__, 3) . '/Action')
        ->tag('controller.service_arguments');

    $services->alias(DependencyFactory::class, 'doctrine.migrations.dependency_factory');

    // The in-app upgrade lock (UpgradeListener) must serialise across every host pointed at
    // the same database, not just concurrent requests on one host, so it is backed by that
    // database rather than by a local file. It opens its own connection deliberately: it is
    // held while DDL runs and, on the released tags, while DDL fails, and on PostgreSQL a
    // failed statement leaves the session unusable - the lock must not share a connection
    // with the migration it is guarding.
    $services->set(LockFactory::class)
        ->arg('$store', inline_service(DoctrineDbalStore::class)
            ->arg('$connOrUrl', env('SOLIDINVOICE_DATABASE_URL')->resolve()));
};
