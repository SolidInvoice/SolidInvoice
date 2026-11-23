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

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use SolidInvoice\CoreBundle\Doctrine\Filter\ArchivableFilter;
use SolidInvoice\CoreBundle\Doctrine\Filter\CompanyEnabledFilter;
use SolidInvoice\CoreBundle\Doctrine\Filter\CompanyFilter;
use SolidInvoice\CoreBundle\Doctrine\Function\ToNumberFunction;
use SolidInvoice\CoreBundle\Doctrine\Type\BigIntegerType;
use SolidInvoice\CoreBundle\Doctrine\Type\QuantityType;

return App::config([
    'doctrine' => [
        'dbal' => [
            'connections' => [
                'default' => [
                    'url' => env('SOLIDINVOICE_DATABASE_URL')->resolve(),
                    // No hardcoded server_version: it forces the platform for every driver
                    // (e.g. MariaDB was detected as plain MySQL, breaking schema introspection
                    // and the doctrine/migrations metadata check). DBAL detects the server
                    // version at runtime, and SQLite does not need one at all.
                    'charset' => 'UTF8',
                    // DBAL 4.6 reverse-maps PostgreSQL `timestamp` to `datetime_immutable`, which
                    // SolidWorxPlatformBundle overrides with the timestamptz UTCDateTimeType. A
                    // naive column then introspects as timestamptz: the migrations metadata check
                    // fails on migration_versions.executed_at, and Version30104_1 no longer sees
                    // the 3.0.x columns it must convert. Keep the pre-4.6 mutable mapping.
                    'mapping_types' => [
                        'timestamp' => 'datetime',
                    ],
                ],
            ],
            'types' => [
                BigIntegerType::NAME => [
                    'class' => BigIntegerType::class,
                ],
                QuantityType::NAME => [
                    'class' => QuantityType::class,
                ],
            ],
        ],
        'orm' => [
            'entity_managers' => [
                'default' => [
                    'auto_mapping' => true,
                    'validate_xml_mapping' => true,
                    'identity_generation_preferences' => [
                        PostgreSQLPlatform::class => 'identity',
                    ],
                    'dql' => [
                        'string_functions' => [
                            'to_number' => ToNumberFunction::class,
                        ],
                    ],
                    'filters' => [
                        'company' => [
                            'class' => CompanyFilter::class,
                            'enabled' => true,
                        ],
                        'company_enabled' => [
                            'class' => CompanyEnabledFilter::class,
                            'enabled' => true,
                        ],
                        'archivable' => [
                            'class' => ArchivableFilter::class,
                            'enabled' => true,
                        ],
                    ],
                    'mappings' => [
                        'payum' => [
                            'is_bundle' => false,
                            'type' => 'xml',
                            'dir' => param('kernel.project_dir') . '/vendor/payum/core/Payum/Core/Bridge/Doctrine/Resources/mapping',
                            'prefix' => 'Payum\Core\Model',
                        ],
                    ],
                ],
            ],
        ],
    ],
]);
