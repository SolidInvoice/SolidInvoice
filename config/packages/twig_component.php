<?php

declare(strict_types=1);

use Symfony\Config\TwigComponentConfig;
use function Symfony\Component\DependencyInjection\Loader\Configurator\param;

return static function (TwigComponentConfig $config): void {
    $config
        ->anonymousTemplateDirectory('components/')
        ->controllersJson(param('kernel.project_dir').'/assets/js/controllers.json')
        ->defaults('SolidInvoice\SettingsBundle\Twig\Components\\', '@SolidInvoiceSettings/Components')
    ;
};
