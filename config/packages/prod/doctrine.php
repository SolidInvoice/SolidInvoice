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

use Symfony\Config\DoctrineConfig;

return static function (DoctrineConfig $config): void {
    $em = $config
        ->orm()
        ->autoGenerateProxyClasses(false)
        ->entityManager('default');

    $em
        ->metadataCacheDriver()
        ->type('pool')
        ->pool('doctrine.system_cache_pool');

    $em
        ->queryCacheDriver()
        ->type('pool')
        ->pool('doctrine.system_cache_pool');

    $em
        ->resultCacheDriver()
        ->type('pool')
        ->pool('doctrine.result_cache_pool');
};
