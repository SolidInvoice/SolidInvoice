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

use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;
use SolidWorx\Platform\PlatformBundle\Feature\SubscriberResolver;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $parameters = $containerConfigurator->parameters();

    $parameters->set('env(database_name)', 'solidinvoice_test');

    $services = $containerConfigurator->services();

    $services->defaults()
        ->autowire()
        ->autoconfigure()
        ->public()
        ->bind('$projectDir', '%kernel.project_dir%');

    // Expose wiring-contract aliases publicly so functional smoke tests can
    // assert the correct concrete implementation is resolved.
    $services->alias('test.' . FeatureGate::class, FeatureGate::class)->public();
    $services->alias('test.' . SubscriberResolver::class, SubscriberResolver::class)->public();
};
