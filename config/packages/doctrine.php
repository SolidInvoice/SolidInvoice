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

use Ramsey\Uuid\Doctrine\UuidBinaryOrderedTimeType;
use Ramsey\Uuid\Doctrine\UuidType;
use SolidInvoice\CoreBundle\Doctrine\Filter\ArchivableFilter;
use SolidInvoice\CoreBundle\Doctrine\Filter\CompanyFilter;
use SolidInvoice\CoreBundle\Doctrine\Type\BigIntegerType;
use Symfony\Config\DoctrineConfig;
use function Symfony\Component\DependencyInjection\Loader\Configurator\env;
use function Symfony\Component\DependencyInjection\Loader\Configurator\param;

return static function (DoctrineConfig $config): void {
    $dbalConfig = $config->dbal();

    $ormConfig = $config->orm();

    $dbalConfig
        ->connection('default')
        ->driver(env('database_driver'))
        ->host(env('database_host'))
        ->port(env('database_port')->int())
        ->dbname(env('database_name'))
        ->user(env('database_user'))
        ->password(env('database_password'))
        ->serverVersion(env('database_version'))
        ->charset('UTF8')
        ->useSavepoints(true)
    ;

    $dbalConfig
        ->type(UuidType::NAME)
        ->class(UuidType::class);

    $dbalConfig
        ->type(UuidBinaryOrderedTimeType::NAME)
        ->class(UuidBinaryOrderedTimeType::class);

    $dbalConfig
        ->type(BigIntegerType::NAME)
        ->class(BigIntegerType::class);

    $ormConfig->autoGenerateProxyClasses(param('kernel.debug'));

    $entityManagerConfig = $ormConfig->entityManager('default');

    $entityManagerConfig
        ->autoMapping(true);

    $entityManagerConfig
        ->filter('company')
        ->enabled(true)
        ->class(CompanyFilter::class);

    $entityManagerConfig
        ->filter('archivable')
        ->enabled(true)
        ->class(ArchivableFilter::class);

    $entityManagerConfig->mapping('payum')
        ->isBundle(false)
        ->type('xml')
        ->dir(param('kernel.project_dir') . '/vendor/payum/core/Payum/Core/Bridge/Doctrine/Resources/mapping')
        ->prefix('Payum\Core\Model');
};
