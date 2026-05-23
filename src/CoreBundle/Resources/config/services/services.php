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

use Gedmo\Timestampable\TimestampableListener;
use Mpociot\VatCalculator\VatCalculator;
use SolidInvoice\CoreBundle\Contracts\EmailVerificationGateInterface;
use SolidInvoice\CoreBundle\DummyData\DummyDataLoader;
use SolidInvoice\CoreBundle\Email\NullEmailVerificationGate;
use SolidInvoice\CoreBundle\Export\Serializer\ExportSerializer;
use SolidInvoice\CoreBundle\Feature\NullUpgradePromptProvider;
use SolidInvoice\CoreBundle\Feature\UpgradePromptProvider;
use SolidInvoice\CoreBundle\Form\Extension\FeatureRestrictedExtension;
use SolidInvoice\CoreBundle\Routing\Loader\AbstractDirectoryLoader;
use SolidInvoice\CoreBundle\Search\MultiSearchService;
use SolidInvoice\CoreBundle\Search\SearchQueryParser;
use SolidInvoice\CoreBundle\SolidInvoiceCoreBundle;
use SolidInvoice\CoreBundle\Twig\Extension\FeatureExtension;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Serializer as SymfonySerializer;
use Symfony\Component\Uid\Command\GenerateUlidCommand;
use Symfony\Component\Uid\Command\GenerateUuidCommand;
use Symfony\Component\Uid\Command\InspectUlidCommand;
use Symfony\Component\Uid\Command\InspectUuidCommand;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;
use function Symfony\Component\DependencyInjection\Loader\Configurator\env;
use function Symfony\Component\DependencyInjection\Loader\Configurator\inline_service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services
        ->defaults()
        ->autowire()
        ->autoconfigure()
        ->private()
        ->bind('$projectDir', param('kernel.project_dir'))
        ->bind('$cacheDir', param('kernel.cache_dir'))
        ->bind('$installed', env('SOLIDINVOICE_INSTALLED'))
        ->bind('$applicationUrl', env('SOLIDINVOICE_APPLICATION_URL'))
        ->bind('$vault', service('secrets.vault'))
    ;

    $services
        ->load(SolidInvoiceCoreBundle::NAMESPACE . '\\', dirname(__DIR__, 3))
        // Export/Serializer/Normalizer is excluded so the normalizers there are NOT
        // registered as global `serializer.normalizer` services. They are loaded
        // inline as fresh instances inside the dedicated export Serializer below
        // (see solidinvoice.core.export.serializer) so they never pollute the API
        // Platform normalizer chain.
        ->exclude([
            dirname(__DIR__, 3) . '/{DependencyInjection,Entity,Resources,Tests,Export/Serializer/Normalizer}',
            dirname(__DIR__, 3) . '/Twig/Extension/FeatureExtension.php',
            dirname(__DIR__, 3) . '/Form/Extension/FeatureRestrictedExtension.php',
        ]);

    // The no-op FeatureExtension shadows three SaaS-only Twig function names so
    // self-hosted templates can call them safely. SaaS deployments register the
    // real implementations from SolidInvoice\SaasBundle\Twig\FeatureExtension —
    // registering both would duplicate the function names and trigger a Twig
    // "function already defined" error at compile time. The same pattern applies
    // to FeatureRestrictedExtension: SaasBundle ships the real form-extension and
    // CoreBundle ships a no-op so the `feature_gated` form option remains valid.
    if (($_ENV['SOLIDINVOICE_PLATFORM'] ?? $_SERVER['SOLIDINVOICE_PLATFORM'] ?? null) !== 'saas') {
        $services->set(FeatureExtension::class);
        $services->set(FeatureRestrictedExtension::class);
    }

    $services
        ->load(SolidInvoiceCoreBundle::NAMESPACE . '\\Action\\', dirname(__DIR__, 3) . '/Action')
        ->autowire(true)
        ->tag('controller.service_arguments');

    $services
        ->load(SolidInvoiceCoreBundle::NAMESPACE . '\\Export\\Action\\', dirname(__DIR__, 3) . '/Export/Action')
        ->autowire(true)
        ->tag('controller.service_arguments');

    $services->set(NullEmailVerificationGate::class);
    $services->alias(
        EmailVerificationGateInterface::class,
        NullEmailVerificationGate::class,
    );

    $services->set(NullUpgradePromptProvider::class);
    $services->alias(
        UpgradePromptProvider::class,
        NullUpgradePromptProvider::class,
    );

    $services
        ->set(TimestampableListener::class)
        ->tag('doctrine.event_subscriber')
    ;

    $services->set(CssToInlineStyles::class);

    $services
        ->set(AbstractDirectoryLoader::class)
        ->lazy()
        ->abstract()
        ->arg('$locator', service('file_locator'))
        ->arg('$kernel', service('kernel'));

    $services->set(VatCalculator::class);

    $services->set(GenerateUlidCommand::class);
    $services->set(GenerateUuidCommand::class);
    $services->set(InspectUlidCommand::class);
    $services->set(InspectUuidCommand::class);

    $services->set(DummyDataLoader::class)
        ->arg('$loaders', tagged_iterator('solidinvoice.dummy_data_loader', defaultPriorityMethod: 'getPriority'));

    $services->set(MultiSearchService::class)
        ->arg('$formatters', tagged_iterator('solidinvoice.search.result_formatter'))
        ->arg('$indexPrefix', env('SOLIDINVOICE_MEILISEARCH_PREFIX'));

    $services->set(SearchQueryParser::class)
        ->arg('$formatters', tagged_iterator('solidinvoice.search.result_formatter'));

    // Dedicated encoder chain for the export feature. The export pipeline does its
    // own value normalisation (GridRowExtractor / EntityRowNormalizer produce flat
    // associative arrays), so the inner Serializer is wired with NO normalizers and
    // fresh encoder instances. Reusing the global `serializer.encoder.*` services or
    // the global `serializer.normalizer.object` would cause Symfony's Serializer
    // constructor to call setSerializer() on the shared instance and silently
    // replace the API Platform serializer reference — breaking JSON-LD output.
    $services->set('solidinvoice.core.export.serializer', SymfonySerializer::class)->arg('$normalizers', [])->arg('$encoders', [
        inline_service(JsonEncoder::class),
        inline_service(CsvEncoder::class),
        inline_service(XmlEncoder::class),
    ]);

    $services->set(ExportSerializer::class)->arg('$inner', service('solidinvoice.core.export.serializer'));
};
