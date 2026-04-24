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

use SolidInvoice\McpBundle\Action\DynamicClientRegistration;
use SolidInvoice\McpBundle\OAuth\KeyManager;
use SolidInvoice\McpBundle\OAuth\PendingAuthorization;
use SolidInvoice\McpBundle\OAuth\ServerFactory;
use SolidInvoice\McpBundle\OAuth\ServerFactoryInterface;
use SolidInvoice\McpBundle\Repository\McpAccessTokenRepository;
use SolidInvoice\McpBundle\Repository\McpRefreshTokenRepository;
use SolidInvoice\McpBundle\Repository\McpScopeRepository;
use SolidInvoice\McpBundle\Repository\OAuthAuthCodeRepository;
use SolidInvoice\McpBundle\Repository\OAuthClientRepository;
use SolidInvoice\McpBundle\SolidInvoiceMcpBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->defaults()
        ->autowire()
        ->autoconfigure()
        ->private();

    $services
        ->load(SolidInvoiceMcpBundle::NAMESPACE . '\\', dirname(__DIR__, 2))
        ->exclude(dirname(__DIR__, 2) . '/{DependencyInjection,Entity,Resources,Tests,SolidInvoiceMcpBundle.php}');

    $services
        ->load(SolidInvoiceMcpBundle::NAMESPACE . '\\Action\\', dirname(__DIR__, 2) . '/Action')
        ->public()
        ->tag('controller.service_arguments');

    $services->set(KeyManager::class)
        ->args([
            '%env(SOLIDINVOICE_CONFIG_DIR)%',
            '%env(SOLIDINVOICE_APP_SECRET)%',
        ]);

    $services->set(PendingAuthorization::class);

    $services->alias(ServerFactoryInterface::class, ServerFactory::class);

    $services->set(ServerFactory::class)
        ->args([
            service(KeyManager::class),
            service(OAuthClientRepository::class),
            service(McpAccessTokenRepository::class),
            service(McpRefreshTokenRepository::class),
            service(OAuthAuthCodeRepository::class),
            service(McpScopeRepository::class),
            '%env(SOLIDINVOICE_MCP_ACCESS_TOKEN_TTL)%',
            '%env(SOLIDINVOICE_MCP_REFRESH_TOKEN_TTL)%',
            '%env(SOLIDINVOICE_MCP_AUTH_CODE_TTL)%',
        ]);

    $services->set(DynamicClientRegistration::class)
        ->public()
        ->tag('controller.service_arguments')
        ->args([
            service(OAuthClientRepository::class),
            service('limiter.mcp_oauth_register')->nullOnInvalid(),
        ]);
};
