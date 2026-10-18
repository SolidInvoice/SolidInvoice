<?php

// User's service configuration file
// This file is loaded into the Symfony DI container

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->parameters()
        // The command your coding agent must use to run Mate. It is materialized into the
        // generated agent instructions, so a container prefix ends up where the agent reads it.
        ->set('mate.invocation', 'symfony php bin/mate')

        // The PHP version this project runs on. Mate refuses to start under a different
        // major.minor, because it would then report on a runtime that is not your application's
        // and extensions may behave differently. Set to null to disable the check.
        ->set('mate.php_version', '8.5')

        // Override default parameters here
        // ->set('mate.cache_dir', sys_get_temp_dir().'/mate')
        // ->set('mate.env_file', '.env') // Loads the project's own .env and .env.local
    ;

    $container->services()
        ->defaults()
            ->autowire()
            ->autoconfigure()

        // Register your custom services here
    ;
};
