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

use SolidInvoice\ApiBundle\Event\Listener\AuthenticationFailHandler;
use SolidInvoice\ApiBundle\Event\Listener\AuthenticationSuccessHandler;
use SolidInvoice\ApiBundle\Security\ApiTokenAuthenticator;
use SolidInvoice\ApiBundle\Security\Provider\ApiTokenUserProvider;
use SolidInvoice\McpBundle\Security\McpOAuthAuthenticator;
use SolidInvoice\McpBundle\Security\McpOAuthUserProvider;
use SolidInvoice\UserBundle\Security\OAuth\OAuthAuthenticator;
use SolidWorx\Platform\PlatformBundle\DependencyInjection\Extension\LoginExtension;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Config\SecurityConfig;

return static function (SecurityConfig $config): void {
    $config
        ->passwordHasher(PasswordAuthenticatedUserInterface::class)
        ->algorithm('auto');

    $config
        ->roleHierarchy('ROLE_ADMIN', [])
        ->roleHierarchy('ROLE_SUPER_ADMIN', ['ROLE_ADMIN', 'ROLE_ALLOWED_TO_SWITCH'])
        ->roleHierarchy('ROLE_CLIENT', ['ROLE_USER'])
        ->roleHierarchy('ROLE_USER', []);

    $config
        ->provider('api_token_user_provider')
        ->id(ApiTokenUserProvider::class);

    $config
        ->provider('mcp_oauth_user_provider')
        ->id(McpOAuthUserProvider::class);

    $config
        ->firewall('assets')
        ->pattern('^/(_(profiler|wdt)|css|images|js)/')
        ->security(false);

    $config
        ->firewall('api_doc')
        ->pattern('^/api/docs')
        ->lazy(true)
        ->security(false);

    $config
        ->firewall('installation')
        ->pattern('^/install')
        ->security(false);

    $config
        ->firewall('api_login')
        ->pattern('^/api/login')
        ->stateless(true)
        ->security(false)
        ->formLogin()
        ->provider('api_token_user_provider')
        ->checkPath('/api/login')
        ->successHandler(AuthenticationSuccessHandler::class)
        ->failureHandler(AuthenticationFailHandler::class);

    $config
        ->firewall('api')
        ->pattern('^/api')
        ->stateless(true)
        ->provider('api_token_user_provider')
        ->customAuthenticators([ApiTokenAuthenticator::class]);

    $config
        ->firewall('mcp_oauth_endpoints')
        ->pattern('^/oauth/(token|register|revoke)$')
        ->stateless(true)
        ->security(false);

    $config
        ->firewall('mcp_well_known')
        ->pattern('^/\.well-known/(oauth-authorization-server|oauth-protected-resource|mcp/server-card\.json|agent-skills/index\.json)')
        ->stateless(true)
        ->security(false);

    $config
        ->firewall('api_well_known')
        ->pattern('^/\.well-known/api-catalog$')
        ->stateless(true)
        ->security(false);

    $config
        ->firewall('mcp')
        ->pattern('^/_mcp')
        ->stateless(true)
        ->provider('mcp_oauth_user_provider')
        ->customAuthenticators([McpOAuthAuthenticator::class]);

    $mainFirewallConfig = LoginExtension::configureDefaultFormLogin($config, true);

    $mainFirewallConfig
        ->customAuthenticators([OAuthAuthenticator::class]);

    $mainFirewallConfig
        ->formLogin()
        ->defaultTargetPath('_select_company')
    ;

    $config->accessControl()
        ->path('^(?:' .
            '/_components/SystemInstallation|' .
            '/webhook/lemon_squeezy|' .
            '/view/(?:quote|invoice)/[A-Za-z0-9-]{36}(?:\.pdf)?|' .
            '/(?:login|register)$|' .
            '/forgot-password|' .
            '/oauth/connect|' .
            '/oauth/(token|register|revoke)$|' .
            '/\.well-known/oauth-authorization-server|' .
            '/\.well-known/oauth-protected-resource|' .
            '/\.well-known/mcp/server-card\.json$|' .
            '/\.well-known/agent-skills/index\.json$|' .
            '/\.well-known/api-catalog$|' .
            '/install|' .
            '/verify$|' .
            '/logout$|' .
            '/invite/accept/[a-zA-Z0-9-]{26}$|' .
            '/payments/create/[a-zA-Z0-9-]{36}$|' .
            '/payment/capture/(?:.*)|' .
            '/payments/done$' .
            ')')
        ->roles(['PUBLIC_ACCESS']);

    $config->accessControl()
        ->path('^/')
        ->roles(['ROLE_USER']);

    $config->accessControl()
        ->path('^/2fa')
        ->roles(['IS_AUTHENTICATED_2FA_IN_PROGRESS']);
};
