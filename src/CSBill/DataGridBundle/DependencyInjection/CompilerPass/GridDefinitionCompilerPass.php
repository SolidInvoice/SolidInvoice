<?php
/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\DataGridBundle\DependencyInjection\CompilerPass;

use CSBill\DataGridBundle\DependencyInjection\GridConfiguration;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Config\FileLocator;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Yaml\Yaml;

class GridDefinitionCompilerPass implements CompilerPassInterface
{
    /**
     * @var KernelInterface
     */
    private $kernel;

    /**
     * @param KernelInterface $kernel
     */
    public function __construct(KernelInterface $kernel)
    {
	$this->kernel = $kernel;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
	$resourceLocator = new FileLocator($this->kernel);
	$definition = $container->getDefinition('grid.repository');

	$configs = [];

	foreach ($this->kernel->getBundles() as $bundle) {
	    try {
		$file = $resourceLocator->locate(sprintf('@%s/Resources/config/grid.yml', $bundle->getName()));
	    } catch (\InvalidArgumentException $e) {
		continue;
	    }

	    $grid = Yaml::parse(file_get_contents($file));

	    $config = $this->processConfiguration($grid);

	    $this->setGridDefinition($definition, $config);
	}

	$container->setParameter('grid.definitions', $configs);
    }

    private function processConfiguration(array $grid)
    {
	$process = new Processor();
	$config = new GridConfiguration();

	return $process->processConfiguration($config, $grid);
    }

    /**
     * @param Definition $gridService
     * @param array      $config
     */
    private function setGridDefinition(Definition $gridService, array $config)
    {
	foreach ($config as $gridName => $gridConfig) {
	    $gridDefinition = new Definition('CSBill\\DataGridBundle\\Grid');

	    $gridConfig['name'] = $gridName;

	    foreach ($gridConfig['filters'] as &$filter) {
		if (array_key_exists('source', $filter)) {
		    $def = new Definition();
		    $def->setFactory([new Reference('doctrine.orm.entity_manager'), 'getRepository']);
		    $def->setArguments([$filter['source']['repository']]);

		    $filter['data'] = $def;
		}
	    }

	    $gridDefinition->addArgument($gridConfig);

	    $gridService->addMethodCall('addGrid', [$gridName, $gridDefinition]);
	}

    }
}