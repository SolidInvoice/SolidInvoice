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

use Symfony\Config\TwigConfig;
use function Symfony\Component\DependencyInjection\Loader\Configurator\param;

return static function (TwigConfig $config): void {
    $config
        ->debug(param('kernel.debug'))
        ->strictVariables(param('kernel.debug'))
        ->fileNamePattern('*.twig')
        ->formThemes([
            '@SolidInvoiceNotification/Form/fields.html.twig',
            '@SolidInvoiceCore/Form/fields.html.twig',
        ]);
};
