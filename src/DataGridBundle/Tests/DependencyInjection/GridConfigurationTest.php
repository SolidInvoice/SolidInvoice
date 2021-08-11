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

namespace SolidInvoice\DataGridBundle\Tests\DependencyInjection;

use Matthias\SymfonyConfigTest\PhpUnit\ConfigurationTestCaseTrait;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use SolidInvoice\DataGridBundle\DependencyInjection\GridConfiguration;
use Symfony\Component\Yaml\Yaml;

class GridConfigurationTest extends TestCase
{
    use ConfigurationTestCaseTrait;
    use MockeryPHPUnitIntegration;

    public const FIXTURES_PATH = __DIR__.'/../fixtures/config/';

    protected function getConfiguration()
    {
        return new GridConfiguration();
    }

    /**
     * @dataProvider invalidConfigProvider
     */
    public function testInvalidGridConfig($message, $config)
    {
        $this->assertConfigurationIsInvalid(['datagrid' => $config], $message ?: null);
    }

    /**
     * @dataProvider validConfigProvider
     */
    public function testValidGridConfig($config)
    {
        $this->assertConfigurationIsValid($config);
    }

    public function testProcessedValueContainsRequiredValue()
    {
        $this->assertProcessedConfigurationEquals(
            Yaml::parse(file_get_contents(realpath(self::FIXTURES_PATH.'valid.yml')))[0],
            [
                'active_client_grid' => [
                    'title' => 'Active',
                    'icon' => 'check',
                    'source' => [
                        'repository' => 'SolidInvoiceClientBundle:Client',
                        'method' => 'getGridQuery',
                    ],
                    'columns' => [
                        'id' => [
                            'name' => 'id',
                            'label' => 'ID',
                            'editable' => false,
                            'cell' => 'integer',
                            'formatter' => null,
                        ],
                        'name' => [
                            'name' => 'name',
                            'label' => 'Name',
                            'editable' => false,
                            'cell' => 'string',
                            'formatter' => null,
                        ],
                        'website' => [
                            'name' => 'website',
                            'label' => 'Website',
                            'editable' => false,
                            'cell' => 'uri',
                            'formatter' => null,
                        ],
                        'status' => [
                            'name' => 'status',
                            'label' => 'Status',
                            'editable' => false,
                            'cell' => 'client_status',
                            'formatter' => null,
                        ],
                        'created' => [
                            'name' => 'created',
                            'label' => 'Created',
                            'editable' => false,
                            'cell' => 'date',
                            'formatter' => null,
                        ],
                    ],
                    'search' => [
                        'fields' => [
                            'name',
                            'website',
                            'status',
                        ],
                    ],
                    'line_actions' => [
                        'view' => [
                            'icon' => 'eye',
                            'label' => 'client.grid.actions.view',
                            'route' => '_clients_view',
                            'route_params' => [
                                'id' => 'id',
                            ],
                            'conditions' => [],
                        ],
                        'edit' => [
                            'icon' => 'edit',
                            'label' => 'client.grid.actions.edit',
                            'route' => '_clients_edit',
                            'route_params' => [
                                'id' => 'id',
                            ],
                            'conditions' => [],
                        ],
                    ],
                    'actions' => [
                        'archive' => [
                            'label' => 'Archive',
                            'icon' => 'archive',
                            'confirm' => 'Are you sure you want to archive the selected rows?',
                            'action' => '_clients_archive',
                            'className' => 'warning',
                        ],
                        'delete' => [
                            'label' => 'Delete',
                            'icon' => 'ban',
                            'confirm' => 'Are you sure you want to delete the selected rows?',
                            'action' => '_clients_delete',
                            'className' => 'danger',
                        ],
                    ],
                    'properties' => [
                        'sortable' => true,
                        'paginate' => true,
                        'route' => null,
                    ],
                    'filters' => [],
                ],
            ]
        );
    }

    public function invalidConfigProvider()
    {
        foreach (Yaml::parse(file_get_contents(realpath(self::FIXTURES_PATH.'invalid.yml'))) as $config) {
            yield [$config['message'] ?? '', $config['datagrid']];
        }
    }

    public function validConfigProvider()
    {
        foreach (Yaml::parse(file_get_contents(realpath(self::FIXTURES_PATH.'valid.yml'))) as $config) {
            yield [$config];
        }
    }
}
