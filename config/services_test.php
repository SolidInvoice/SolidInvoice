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
use SolidInvoice\InstallBundle\Listener\UpgradeListener;
use SolidInvoice\SaasBundle\Feature\RequiredPlanLabelProvider;
use SolidInvoice\SaasBundle\Form\Extension\FeatureRestrictedExtension as SaasFeatureRestrictedExtension;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;
use SolidWorx\Platform\PlatformBundle\Feature\SubscriberResolver;
use SolidWorx\Platform\SaasBundle\Feature\FeatureConfigRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator, ContainerBuilder $container): void {
    $parameters = $containerConfigurator->parameters();

    $parameters->set('env(database_name)', 'solidinvoice_test');

    $services = $containerConfigurator->services();

    $services->defaults()
        ->autowire()
        ->autoconfigure()
        ->public()
        ->bind('$projectDir', '%kernel.project_dir%');

    // The test schema is built by tests/bootstrap.php and Foundry, so the migrations
    // metadata table is empty and this listener considers every request's database out
    // of date. It then calls MetadataStorage::ensureInitialized(), which issues
    // CREATE TABLE migration_versions mid-request. On MySQL that DDL implicitly commits
    // the transaction dama/doctrine-test-bundle wraps each test in, taking every
    // savepoint with it ("SAVEPOINT DAMA_TEST does not exist"). SQLite has transactional
    // DDL, which is why this only ever showed up on MySQL.
    //
    // Removed in a compiler pass rather than with $services->remove(): bundle extensions
    // are loaded after this file, so a removal here would just be undone again.
    $container->addCompilerPass(new class() implements CompilerPassInterface {
        public function process(ContainerBuilder $container): void
        {
            $container->removeDefinition(UpgradeListener::class);
        }
    }, priority: -256);

    // Expose wiring-contract aliases publicly so functional smoke tests can
    // assert the correct concrete implementation is resolved.
    $services->alias('test.' . FeatureGate::class, FeatureGate::class);
    $services->alias('test.' . SubscriberResolver::class, SubscriberResolver::class);
    $services->alias('test.' . UpgradePromptProvider::class, UpgradePromptProvider::class);

    // FeatureConfigRegistry is registered by SaasBundle, which is only loaded
    // when the app mode is 'saas'. Mirror the same gate from bundles.php so
    // the alias is only declared when the underlying service exists.
    if ($container->getParameter('app_mode') === 'saas') {
        $services->alias('test.' . FeatureConfigRegistry::class, FeatureConfigRegistry::class);
        $services->alias('test.' . RequiredPlanLabelProvider::class, RequiredPlanLabelProvider::class);
        $services->alias('test.' . SaasFeatureRestrictedExtension::class, SaasFeatureRestrictedExtension::class);
    }
};
