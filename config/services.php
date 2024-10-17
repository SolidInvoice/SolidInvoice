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

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\UX\StimulusBundle\Helper\StimulusHelper;

return static function (ContainerConfigurator $containerConfigurator): void {
    $parameters = $containerConfigurator->parameters();

    $parameters->set('env(database_driver)', 'pdo_mysql');
    $parameters->set('env(database_host)', '127.0.0.1');
    $parameters->set('env(database_port)', '3306');
    $parameters->set('env(database_name)', 'solidinvoice');
    $parameters->set('env(database_user)', 'root');
    $parameters->set('env(database_password)', null);
    $parameters->set('env(database_version)', '1.0');
    $parameters->set('env(mailer_transport)', 'sendmail');
    $parameters->set('env(mailer_host)', '127.0.0.1');
    $parameters->set('env(mailer_user)', null);
    $parameters->set('env(mailer_password)', null);
    $parameters->set('env(mailer_port)', null);
    $parameters->set('env(mailer_encryption)', null);
    $parameters->set('env(locale)', 'en');
    $parameters->set('env(secret)', 'SecretToken');
    $parameters->set('env(installed)', null);
    $parameters->set('env(SOLIDINVOICE_ALLOW_REGISTRATION)', '0');
    $parameters->set('env(SENTRY_DSN)', null);
    $parameters->set('env(MAILER_DSN)', 'null://null');
    $parameters->set('env(SENTRY_SEND_DEFAULT_PII)', '0');
    $parameters->set('.container.dumper.inline_factories', false);

    $containerConfigurator->services()
        ->set(Monolog\Processor\PsrLogMessageProcessor::class)
        ->tag('monolog.processor', ['handler' => 'sentry']);

    $services = $containerConfigurator->services();

    $services->alias(StimulusHelper::class, 'stimulus.helper');
};
