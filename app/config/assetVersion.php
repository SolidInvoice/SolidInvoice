<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

$config = [
    'templating' => [
        'engines' => ['twig'],
    ],
    'assets' => [
        'version' => CSBill\CoreBundle\CSBillCoreBundle::VERSION,
    ]
];

if ($container->hasParameter('base_url')) {
    $config['assets']['base_urls'] = ['%base_url%'];
}

$container->loadFromExtension(
    'framework',
    $config
);
