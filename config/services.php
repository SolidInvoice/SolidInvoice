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

use SolidInvoice\AppRequirements;
use SolidInvoice\CoreBundle\Search\DoctrineEventSubscriberDecorator;
use SolidInvoice\CoreBundle\SolidInvoiceCoreBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\UX\StimulusBundle\Helper\StimulusHelper;
use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $containerConfigurator): void {
    $parameters = $containerConfigurator->parameters();

    $parameters->set('env(SOLIDINVOICE_DATABASE_URL)', 'sqlite:///%env(SOLIDINVOICE_CONFIG_DIR)%/db/solidinvoice.db');

    $parameters->set('env(SOLIDINVOICE_LOCALE)', 'en');
    $parameters->set('env(SOLIDINVOICE_APP_SECRET)', null);
    $parameters->set('env(SOLIDINVOICE_INSTALLED)', null);
    $parameters->set('env(SOLIDINVOICE_APPLICATION_URL)', '');
    $parameters->set('env(SOLIDINVOICE_CUSTOM_DOMAIN_DNS_RECORD)', '');
    $parameters->set('env(SOLIDINVOICE_RUNTIME)', null);
    $parameters->set('env(SOLIDINVOICE_ALLOW_REGISTRATION)', '0');
    $parameters->set('env(SOLIDINVOICE_OAUTH_CLIENT_GOOGLE_CLIENT_ID)', null);
    $parameters->set('env(SOLIDINVOICE_OAUTH_CLIENT_GOOGLE_CLIENT_SECRET)', null);

    $parameters->set('env(SOLIDINVOICE_SENTRY_DSN)', null);
    $parameters->set('env(SOLIDINVOICE_SENTRY_RELEASE)', '');
    $parameters->set('env(SOLIDINVOICE_SENTRY_SEND_DEFAULT_PII)', '0');
    $parameters->set('env(SOLIDINVOICE_SENTRY_TRACES_SAMPLE_RATE)', '0');
    $parameters->set('env(SOLIDINVOICE_SENTRY_PROFILES_SAMPLE_RATE)', '0');
    $parameters->set('env(SOLIDINVOICE_SENTRY_HTTP_TIMEOUT)', '2');
    $parameters->set('env(SOLIDINVOICE_SENTRY_HTTP_CONNECT_TIMEOUT)', '2');
    $parameters->set('env(SOLIDINVOICE_MAILER_DSN)', 'null://null');
    $parameters->set('env(SOLIDINVOICE_MAILER_SENDER)', 'SolidInvoice <no-reply@solidinvoice.co>');
    $parameters->set('env(SOLIDINVOICE_MESSENGER_DSN)', 'doctrine://default?queue_name=async');
    $parameters->set('env(SOLIDINVOICE_PLATFORM)', null);

    $parameters->set('env(SOLIDINVOICE_MEILISEARCH_URL)', '');
    $parameters->set('env(SOLIDINVOICE_MEILISEARCH_API_KEY)', '');
    $parameters->set('env(SOLIDINVOICE_MEILISEARCH_PREFIX)', 'solidinvoice_%env(SOLIDINVOICE_ENV)%_');

    $parameters->set('env(SOLIDINVOICE_MCP_ACCESS_TOKEN_TTL)', 'P1D');
    $parameters->set('env(SOLIDINVOICE_MCP_REFRESH_TOKEN_TTL)', 'P90D');
    $parameters->set('env(SOLIDINVOICE_MCP_AUTH_CODE_TTL)', 'PT10M');

    if ($containerConfigurator->env() === 'test') {
        $parameters->set('env(SOLIDINVOICE_CONFIG_DIR)', param('kernel.project_dir') . '/var/cache/test/config');
    } else {
        $parameters->set('env(SOLIDINVOICE_CONFIG_DIR)', param('kernel.project_dir') . '/config/env');
    }
    $parameters->set('application_version', SolidInvoiceCoreBundle::VERSION);

    $services = $containerConfigurator->services();

    $services
        ->set(Monolog\Processor\PsrLogMessageProcessor::class)
        ->tag('monolog.processor', ['handler' => 'sentry'])
        ->tag('monolog.processor', ['handler' => 'sentry_logs']);

    $services->alias(StimulusHelper::class, 'stimulus.helper');
    $services->set(AppRequirements::class)
        ->autowire(true);

    $services->set(DoctrineEventSubscriberDecorator::class)
        ->args([
            service('.inner'),
            service('SolidWorx\Toggler\ToggleInterface'),
        ])
        ->decorate('meilisearch.search_indexer_subscriber')
        ->tag('doctrine.event_listener', ['event' => 'postPersist'])
        ->tag('doctrine.event_listener', ['event' => 'postUpdate'])
        ->tag('doctrine.event_listener', ['event' => 'preRemove']);
};
