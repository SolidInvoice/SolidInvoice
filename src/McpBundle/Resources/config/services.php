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
use SolidInvoice\McpBundle\SolidInvoiceMcpBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();
    $services->defaults()->public();

    $services->defaults()
        ->autowire()
        ->autoconfigure()
        ->private();

    $services
        ->load(SolidInvoiceMcpBundle::NAMESPACE . '\\', dirname(__DIR__, 2))
        ->exclude(dirname(__DIR__, 2) . '/{DependencyInjection,Entity,Resources,Tests,SolidInvoiceMcpBundle.php}');

    $services
        ->load(SolidInvoiceMcpBundle::NAMESPACE . '\\Action\\', dirname(__DIR__, 2) . '/Action')
        ->tag('controller.service_arguments');

    $services->set(KeyManager::class)->arg('$configDir', '%env(SOLIDINVOICE_CONFIG_DIR)%')->arg('$encryptionKey', '%env(SOLIDINVOICE_APP_SECRET)%');

    $services->set(PendingAuthorization::class);

    $services->alias(ServerFactoryInterface::class, ServerFactory::class);

    $services->set(ServerFactory::class)->arg('$accessTokenTtl', '%env(SOLIDINVOICE_MCP_ACCESS_TOKEN_TTL)%')->arg('$refreshTokenTtl', '%env(SOLIDINVOICE_MCP_REFRESH_TOKEN_TTL)%')->arg('$authCodeTtl', '%env(SOLIDINVOICE_MCP_AUTH_CODE_TTL)%');

    $services->set(DynamicClientRegistration::class)
        ->tag('controller.service_arguments')->arg('$mcpOauthRegisterLimiter', service('limiter.mcp_oauth_register')->nullOnInvalid());
};
