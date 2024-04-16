<?php

declare(strict_types=1);

use SolidInvoice\CoreBundle\ConfigWriter;
use SolidInvoice\CoreBundle\Test\Csrf\ConsistentCsrfTokenGenerator;
use SolidInvoice\InstallBundle\Installer\Database\Migration;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;

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

    $services->set(CsrfTokenManagerInterface::class)
        ->public();
};
