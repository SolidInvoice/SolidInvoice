<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use SolidInvoice\Kernel;

$runtimeOptions = [
    'env_var_name' => 'SOLIDINVOICE_ENV',
    'debug_var_name' => 'SOLIDINVOICE_DEBUG'
];

$_SERVER['APP_RUNTIME_OPTIONS'] = $runtimeOptions;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return static function (array $context) use ($runtimeOptions) {
    $environment = $context[$runtimeOptions['env_var_name']] ?? null;
    $debug = isset($context[$runtimeOptions['debug_var_name']]) ? (bool) $context[$runtimeOptions['debug_var_name']] : false;

    return new Kernel($environment, $debug);
};
