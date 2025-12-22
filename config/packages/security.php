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
            '/view/(quote|invoice)/[a-zA-Z0-9-]{36}$|' .
            '/(login|register)$|' .
            '/forgot-password|' .
            '/oauth/connect|' .
            '/install(?:.*)|' .
            '/verify$|' .
            '/logout$|' .
            '/invite/accept/[a-zA-Z0-9-]{36}$|' .
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
