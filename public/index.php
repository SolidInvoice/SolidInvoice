<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use SolidInvoice\AppMode;
use SolidInvoice\Kernel;
use SolidInvoice\Runtime;

$_SERVER['APP_RUNTIME'] = Runtime::class;
$_SERVER['APP_RUNTIME_OPTIONS'] = [
    'env_var_name' => 'SOLIDINVOICE_ENV',
    'debug_var_name' => 'SOLIDINVOICE_DEBUG'
];

require_once dirname(__DIR__) . '/vendor/autoload_runtime.php';

return static function (AppMode $mode, array $context) {
    return new Kernel($mode, $context['SOLIDINVOICE_ENV'], (bool) $context['SOLIDINVOICE_DEBUG']);
};
