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

use Symfony\Config\ZenstruckFoundryConfig;

return static function (ZenstruckFoundryConfig $config): void {
    $config->faker()
        ->seed(91847);
};
