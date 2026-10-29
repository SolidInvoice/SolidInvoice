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

use Monolog\Handler\HandlerInterface;
use Monolog\Level;
use Monolog\Processor\PsrLogMessageProcessor;
use Sentry\SentryBundle\Monolog\LogsHandler;
use SolidInvoice\AppRequirements;
use SolidInvoice\CoreBundle\Logger\ServerLogHandlerFactory;
use SolidInvoice\CoreBundle\Search\DoctrineEventSubscriberDecorator;
use SolidInvoice\CoreBundle\SolidInvoiceCoreBundle;
use SolidInvoice\UserBundle\Security\UserChecker;
use SolidWorx\Platform\PlatformBundle\Security\EmailVerification\VerifiedUserChecker;
use SolidWorx\Toggler\ToggleInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\UX\StimulusBundle\Helper\StimulusHelper;
use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $containerConfigurator): void {
    $parameters = $containerConfigurator->parameters();

    $parameters->set('env(SOLIDINVOICE_DATABASE_URL)', 'sqlite:///%env(SOLIDINVOICE_CONFIG_DIR)%/db/solidinvoice.db');
    $parameters->set('env(SOLIDINVOICE_LOCALE)', 'en');
    $parameters->set('env(SOLIDINVOICE_APP_SECRET)', null);
    // Fallback until the installer writes SOLIDINVOICE_APP_SECRET; framework.secret only uses it while that env var is unset or empty
    $parameters->set('solidinvoice.fallback_secret', bin2hex(random_bytes(16)));
    $parameters->set('env(SOLIDINVOICE_INSTALLED)', null);
    $parameters->set('env(SOLIDINVOICE_APPLICATION_URL)', '');
    $parameters->set('env(SOLIDINVOICE_CUSTOM_DOMAIN_DNS_RECORD)', '');
    $parameters->set('env(SOLIDINVOICE_LOCK_DSN)', 'flock');
    $parameters->set('env(SOLIDINVOICE_RUNTIME)', null);
    $parameters->set('env(SOLIDINVOICE_ALLOW_REGISTRATION)', false);
    $parameters->set('env(SOLIDINVOICE_OAUTH_CLIENT_GOOGLE_CLIENT_ID)', null);
    $parameters->set('env(SOLIDINVOICE_OAUTH_CLIENT_GOOGLE_CLIENT_SECRET)', null);
    $parameters->set('env(SOLIDINVOICE_TURNSTILE_SITE_KEY)', null);
    $parameters->set('env(SOLIDINVOICE_TURNSTILE_SECRET_KEY)', null);
    $parameters->set('env(SOLIDINVOICE_SENTRY_DSN)', null);
    $parameters->set('env(SOLIDINVOICE_SENTRY_RELEASE)', '');
    $parameters->set('env(SOLIDINVOICE_SENTRY_SEND_DEFAULT_PII)', '0');
    $parameters->set('env(SOLIDINVOICE_SENTRY_TRACES_SAMPLE_RATE)', '0');
    $parameters->set('env(SOLIDINVOICE_SENTRY_PROFILES_SAMPLE_RATE)', '0');
    $parameters->set('env(SOLIDINVOICE_SENTRY_HTTP_TIMEOUT)', '2');
    $parameters->set('env(SOLIDINVOICE_SENTRY_HTTP_CONNECT_TIMEOUT)', '2');
    $parameters->set('env(SOLIDINVOICE_MAILER_DSN)', 'null://null');
    $parameters->set('env(SOLIDINVOICE_LOG_LEVEL)', 'info');
    $parameters->set('env(SOLIDINVOICE_MAILER_SENDER)', 'SolidInvoice <no-reply@solidinvoice.co>');
    $parameters->set('env(SOLIDINVOICE_TRANSLATION_DSN)', '');
    $parameters->set('env(SOLIDINVOICE_MESSENGER_DSN)', 'doctrine://default?queue_name=async');
    $parameters->set('env(SOLIDINVOICE_PLATFORM)', null);
    $parameters->set('env(SOLIDINVOICE_MEILISEARCH_URL)', '');
    $parameters->set('env(SOLIDINVOICE_MEILISEARCH_API_KEY)', '');
    $parameters->set('env(SOLIDINVOICE_MEILISEARCH_PREFIX)', 'solidinvoice_%env(SOLIDINVOICE_ENV)%_');
    $parameters->set('env(SOLIDINVOICE_MCP_ACCESS_TOKEN_TTL)', 'P1D');
    $parameters->set('env(SOLIDINVOICE_MCP_REFRESH_TOKEN_TTL)', 'P90D');
    $parameters->set('env(SOLIDINVOICE_MCP_AUTH_CODE_TTL)', 'PT10M');
    $parameters->set('env(SOLIDINVOICE_TELEMETRY_URL)', 'https://insights.solidworx.co');
    $parameters->set('env(SOLIDINVOICE_ENABLE_TELEMETRY)', '0'); // default OFF; '1' enables
    $parameters->set('env(SOLIDINVOICE_INSTALL_TYPE)', '');       // '' → auto-detect (docker vs manual)
    $parameters->set('env(SOLIDINVOICE_AFFILIATE_PROGRAM_ENABLED)', '1');
    $parameters->set('env(SOLIDINVOICE_AFFILIATE_SIGNUP_URL)', 'https://billing.solidinvoice.co/affiliates');
    $parameters->set('env(SOLIDINVOICE_AFFILIATE_TERMS_URL)', '');
    $parameters->set('env(SOLIDINVOICE_AFFILIATE_COMMISSION)', '20%');
    $parameters->set('env(SOLIDINVOICE_AFFILIATE_YEARLY_EARNINGS)', '$93'); // 20% of Business at $39/month
    $parameters->set('env(SOLIDINVOICE_AFFILIATE_PROMPT_DELAY_DAYS)', '3');

    if ($containerConfigurator->env() === 'test') {
        $parameters->set('env(SOLIDINVOICE_CONFIG_DIR)', param('kernel.project_dir') . '/var/cache/test/config');
    } else {
        $parameters->set('env(SOLIDINVOICE_CONFIG_DIR)', param('kernel.project_dir') . '/config/env');
    }

    $parameters->set('application_version', SolidInvoiceCoreBundle::VERSION);

    $services = $containerConfigurator->services();

    $services
        ->set(PsrLogMessageProcessor::class)
        ->tag('monolog.processor', ['handler' => 'sentry'])
        ->tag('monolog.processor', ['handler' => 'sentry_logs']);

    $services->alias(StimulusHelper::class, 'stimulus.helper');

    // The token-based firewalls refuse disabled accounts and, on hosted installs, unverified
    // ones. The web firewall only refuses disabled accounts: an unverified user can sign in and
    // is nudged by the verification banner instead.
    $services->set(UserChecker::class)
        ->tag('security.user_checker.api')
        ->tag('security.user_checker.mcp');

    $services->set(VerifiedUserChecker::class)
        ->autowire()
        ->tag('security.user_checker.api')
        ->tag('security.user_checker.mcp');
    $services->set(AppRequirements::class)
        ->autowire(true);

    $services->set(DoctrineEventSubscriberDecorator::class)->arg('$inner', service('.inner'))->arg('$toggle', service(ToggleInterface::class))
        ->decorate('meilisearch.search_indexer_subscriber')
        ->tag('doctrine.event_listener', ['event' => 'postPersist'])
        ->tag('doctrine.event_listener', ['event' => 'postUpdate'])
        ->tag('doctrine.event_listener', ['event' => 'preRemove']);

    $services->set(LogsHandler::class)->arg('$level', Level::Info);

    $services->set('solidinvoice.log.server_handler', HandlerInterface::class)
        ->factory(ServerLogHandlerFactory::class . '::create')
        ->arg('$level', '%env(SOLIDINVOICE_LOG_LEVEL)%');
};
