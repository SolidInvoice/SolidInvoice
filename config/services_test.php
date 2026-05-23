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

use SolidInvoice\CoreBundle\Feature\UpgradePromptProvider;
use SolidInvoice\SaasBundle\Feature\RequiredPlanLabelProvider;
use SolidInvoice\SaasBundle\Form\Extension\FeatureRestrictedExtension as SaasFeatureRestrictedExtension;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;
use SolidWorx\Platform\PlatformBundle\Feature\SubscriberResolver;
use SolidWorx\Platform\SaasBundle\Feature\FeatureConfigRegistry;
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
    $services->alias('test.' . FeatureGate::class, FeatureGate::class);
    $services->alias('test.' . SubscriberResolver::class, SubscriberResolver::class);
    $services->alias('test.' . UpgradePromptProvider::class, UpgradePromptProvider::class);

    // FeatureConfigRegistry is registered by SaasBundle, which is only loaded
    // when SOLIDINVOICE_PLATFORM=saas. Mirror the same gate from bundles.php so
    // the alias is only declared when the underlying service exists.
    if (($_ENV['SOLIDINVOICE_PLATFORM'] ?? $_SERVER['SOLIDINVOICE_PLATFORM'] ?? null) === 'saas') {
        $services->alias('test.' . FeatureConfigRegistry::class, FeatureConfigRegistry::class);
        $services->alias('test.' . RequiredPlanLabelProvider::class, RequiredPlanLabelProvider::class);
        $services->alias('test.' . SaasFeatureRestrictedExtension::class, SaasFeatureRestrictedExtension::class);
    }
};
