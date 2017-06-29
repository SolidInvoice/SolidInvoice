<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException;

try {
    $baseUrl = $container->getParameter('base_url');
} catch (ParameterNotFoundException $e) {
    $baseUrl = null;
}

$config = [
    'templating' => [
        'engines' => ['twig'],
    ],
    'assets' => [
        'version' => CSBill\CoreBundle\CSBillCoreBundle::VERSION,
    ],
];

if (file_exists(dirname(dirname(__DIR__)).'/web/manifest.json')) {
    $config['assets']['json_manifest_path'] = '%kernel.project_dir%/web/manifest.json';
    unset($config['assets']['version']);
}

if (null !== $baseUrl) {
    $config['assets']['base_urls'] = ['%base_url%'];
}

$container->loadFromExtension(
    'framework',
    $config
);
