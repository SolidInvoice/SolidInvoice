<?php

declare(strict_types=1);

use Symfony\Config\TwigConfig;
use function Symfony\Component\DependencyInjection\Loader\Configurator\param;

return static function (TwigConfig $config): void {
    $config
        ->debug(param('kernel.debug'))
        ->strictVariables(param('kernel.debug'))
        ->formThemes([
            '@SolidInvoiceNotification/Form/fields.html.twig',
            '@SolidInvoiceCore/Form/fields.html.twig',
        ]);
};
