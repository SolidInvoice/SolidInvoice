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
use SolidInvoice\CoreBundle\Doctrine\Filter\CompanyFilter;
use SolidInvoice\CoreBundle\Doctrine\Function\ToNumberFunction;
use SolidInvoice\CoreBundle\Doctrine\Type\BigIntegerType;

return App::config([
    'doctrine' => [
        'dbal' => [
            'connections' => [
                'default' => [
                    'url' => env('SOLIDINVOICE_DATABASE_URL')->resolve(),
                    'server_version' => '3',
                    'charset' => 'UTF8',
                ],
            ],
            'types' => [
                BigIntegerType::NAME => [
                    'class' => BigIntegerType::class,
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
