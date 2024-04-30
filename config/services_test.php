<?php

declare(strict_types=1);

use SolidInvoice\CoreBundle\ConfigWriter;
use SolidInvoice\InstallBundle\Installer\Database\Migration;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $parameters = $containerConfigurator->parameters();

    $parameters->set('env(database_name)', 'solidinvoice_test');

    $services = $containerConfigurator->services();

    $services->defaults()
        ->autowire()
        ->autoconfigure()
        ->bind('$projectDir', '%kernel.project_dir%');

    $services->set(Migration::class)
        ->public();

    $services->set(ConfigWriter::class)
        ->public();
};
