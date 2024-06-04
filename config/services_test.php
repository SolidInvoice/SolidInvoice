<?php

declare(strict_types=1);

use SolidInvoice\CoreBundle\ConfigWriter;
use SolidInvoice\InstallBundle\Installer\Database\Migration;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Twig\Environment;
use Twig\Loader\LoaderInterface;

return static function (ContainerConfigurator $containerConfigurator): void {
    $parameters = $containerConfigurator->parameters();

    $parameters->set('env(database_name)', 'solidinvoice_test');

    $services = $containerConfigurator->services();

    $services->defaults()
        ->autowire()
        ->autoconfigure()
        ->public()
        ->bind('$projectDir', '%kernel.project_dir%');
};
