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
use SolidInvoice\UserBundle\Entity\User;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Config\SecurityConfig;
use function Symfony\Component\DependencyInjection\Loader\Configurator\env;

return static function (SecurityConfig $config): void {
    $config
        ->passwordHasher(User::class)
        ->algorithm('auto');

    $config
        ->passwordHasher(PasswordAuthenticatedUserInterface::class)
        ->algorithm('auto');

    $config
        ->roleHierarchy('ROLE_ADMIN', [])
        ->roleHierarchy('ROLE_SUPER_ADMIN', ['ROLE_ADMIN', 'ROLE_ALLOWED_TO_SWITCH'])
        ->roleHierarchy('ROLE_CLIENT', ['ROLE_USER'])
        ->roleHierarchy('ROLE_USER', []);

    $config
        ->provider('solidinvoice_user')
        ->entity()
        ->class(User::class);

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

    $mainFirewallConfig = $config
        ->firewall('main')
        ->pattern('^/')
        ->lazy(true)
    ;

    $mainFirewallConfig
        ->rememberMe()
        ->secret(env('secret'))
        ->lifetime(3600)
        ->path('/')
        ->domain(null);

    $mainFirewallConfig
        ->formLogin()
        ->provider('solidinvoice_user')
        ->enableCsrf(true)
        ->checkPath('/login-check')
        ->loginPath('/login')
        ->alwaysUseDefaultTargetPath(true)
        ->defaultTargetPath('/select-company');

    $mainFirewallConfig
        ->logout()
        ->path('/logout')
        ->target('/');

    $config->accessControl()
        ->path('^(?:' .
            '/view/(quote|invoice)/[a-zA-Z0-9-]{36}$|' .
            '/(login|register|resetting)$|' .
            '/install(?:.*)|' .
            '/invite/accept/[a-zA-Z0-9-]{36}$|' .
            '/payments/create/[a-zA-Z0-9-]{36}$|' .
            '/payment/capture/(?:.*)|' .
            '/payments/done$' .
        ')')
        ->roles(['PUBLIC_ACCESS']);

    $config->accessControl()
        ->path('^/')
        ->roles(['ROLE_USER'])
    ;
};
