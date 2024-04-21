<?php

declare(strict_types=1);

use SolidInvoice\ApiBundle\Event\Listener\AuthenticationFailHandler;
use SolidInvoice\ApiBundle\Event\Listener\AuthenticationSuccessHandler;
use SolidInvoice\ApiBundle\Security\ApiTokenAuthenticator;
use SolidInvoice\ApiBundle\Security\Provider\ApiTokenUserProvider;
use SolidInvoice\UserBundle\Entity\User;
use Symfony\Config\SecurityConfig;
use function Symfony\Component\DependencyInjection\Loader\Configurator\env;

return static function (SecurityConfig $config): void {

    $config
        ->passwordHasher(User::class)
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
        ->anonymous(true);

    $config
        ->firewall('api_login')
        ->pattern('^/api/login')
        ->stateless(true)
        ->anonymous(true)
        ->formLogin()
            ->provider('api_token_user_provider')
            ->checkPath('/api/login')
            ->requirePreviousSession(false)
            ->successHandler(AuthenticationSuccessHandler::class)
            ->failureHandler(AuthenticationFailHandler::class);

    $config
        ->firewall('api')
        ->pattern('^/api')
        ->stateless(true)
        ->provider('api_token_user_provider')
        ->guard()
            ->authenticators([ApiTokenAuthenticator::class]);

    $mainFirewallConfig = $config
        ->firewall('main')
        ->pattern('^/')
        ->lazy(true);

    $mainFirewallConfig
        ->rememberMe()
        ->secret(env('secret'))
        ->lifetime(3600)
        ->path('/')
        ->domain(null);

    $mainFirewallConfig
        ->formLogin()
        ->provider('solidinvoice_user')
        ->csrfTokenGenerator('security.csrf.token_manager')
        ->checkPath('/login-check')
        ->loginPath('/login')
        ->alwaysUseDefaultTargetPath(true)
        ->defaultTargetPath('/select-company');

    $mainFirewallConfig
        ->logout()
        ->path('/logout')
        ->target('/');

    $config->accessControl()
        ->path('^(?:'.
            '/view/(quote|invoice)/[a-zA-Z0-9-]{36}$|'.
            '/(?:login|register|resetting)$|'.
            '/install(?:.*)|'.
            '/invite/accept/[a-zA-Z0-9-]{36}$|'.
            '/payments/create/[a-zA-Z0-9-]{36}$|'.
            '/payment/capture/(?:.*)|'.
            '/payments/done$'.
        ')')
        ->roles(['PUBLIC_ACCESS']);

    $config->accessControl()
        ->path('^/')
        ->roles(['ROLE_USER'])
    ;
};
