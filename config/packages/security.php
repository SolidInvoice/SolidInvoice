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

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use SolidInvoice\ApiBundle\Event\Listener\AuthenticationFailHandler;
use SolidInvoice\ApiBundle\Event\Listener\AuthenticationSuccessHandler;
use SolidInvoice\ApiBundle\Security\ApiTokenAuthenticator;
use SolidInvoice\ApiBundle\Security\Provider\ApiTokenUserProvider;
use SolidInvoice\McpBundle\Security\McpOAuthAuthenticator;
use SolidInvoice\McpBundle\Security\McpOAuthUserProvider;
use SolidInvoice\UserBundle\Security\OAuth\OAuthAuthenticator;
use SolidInvoice\UserBundle\Security\UserChecker;
use SolidInvoice\UserBundle\Security\VerifiedUserChecker;
use SolidWorx\Platform\PlatformBundle\Model\User;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

// NOTE: The `main` firewall, the `User`/`platform_user` password hasher, the
// `remember_me`, `form_login`, `logout`, `login_throttling` and `two_factor`
// settings below, plus the `^/2fa` access-control rule, were previously provided
// by SolidWorx\Platform\PlatformBundle\DependencyInjection\Extension\LoginExtension::configureDefaultFormLogin()
// (with two-factor enabled) and TwoFactorExtension::configureSecurity().
// Those helpers only work with the deprecated fluent SecurityConfig builder, so
// their output is inlined here. Keep these values in sync with the Platform
// helpers when upgrading solidworx/platform.
return App::config([
    'security' => [
        'password_hashers' => [
            PasswordAuthenticatedUserInterface::class => [
                'algorithm' => 'auto',
            ],
            User::class => [
                'algorithm' => 'auto',
            ],
        ],
        'providers' => [
            'api_token_user_provider' => [
                'id' => ApiTokenUserProvider::class,
            ],
            'mcp_oauth_user_provider' => [
                'id' => McpOAuthUserProvider::class,
            ],
        ],
        'firewalls' => [
            'assets' => [
                'pattern' => '^/(_(profiler|wdt)|css|images|js)/',
                'security' => false,
            ],
            'api_doc' => [
                'pattern' => '^/api/docs',
                'lazy' => true,
                'security' => false,
            ],
            'installation' => [
                'pattern' => '^/install',
                'security' => false,
            ],
            'api_login' => [
                'pattern' => '^/api/login',
                'stateless' => true,
                'security' => false,
                'form_login' => [
                    'provider' => 'api_token_user_provider',
                    'check_path' => '/api/login',
                    'success_handler' => AuthenticationSuccessHandler::class,
                    'failure_handler' => AuthenticationFailHandler::class,
                ],
            ],
            'api' => [
                'pattern' => '^/api',
                'stateless' => true,
                'provider' => 'api_token_user_provider',
                'user_checker' => VerifiedUserChecker::class,
                'custom_authenticators' => [ApiTokenAuthenticator::class],
            ],
            'mcp_oauth_endpoints' => [
                'pattern' => '^/oauth/(token|register|revoke)$',
                'stateless' => true,
                'security' => false,
            ],
            'mcp_well_known' => [
                'pattern' => '^/\.well-known/(oauth-authorization-server|oauth-protected-resource|mcp/server-card\.json|agent-skills/index\.json)',
                'stateless' => true,
                'security' => false,
            ],
            'api_well_known' => [
                'pattern' => '^/\.well-known/api-catalog$',
                'stateless' => true,
                'security' => false,
            ],
            'mcp' => [
                'pattern' => '^/_mcp',
                'stateless' => true,
                'provider' => 'mcp_oauth_user_provider',
                'user_checker' => VerifiedUserChecker::class,
                'custom_authenticators' => [McpOAuthAuthenticator::class],
            ],
            'main' => [
                'pattern' => '^/',
                'entry_point' => 'form_login',
                'provider' => 'platform_user',
                'lazy' => true,
                'user_checker' => UserChecker::class,
                'custom_authenticators' => [OAuthAuthenticator::class],
                'remember_me' => [
                    'lifetime' => 60 * 60 * 24 * 7, // 7 days
                    'path' => '/',
                    'domain' => null,
                ],
                'form_login' => [
                    'provider' => 'platform_user',
                    'login_path' => '/login',
                    'check_path' => '_login_check',
                    'enable_csrf' => true,
                    'always_use_default_target_path' => true,
                    'default_target_path' => '_select_company',
                ],
                'logout' => [
                    'path' => '/logout',
                    'target' => '/',
                    'clear_site_data' => ['cookies', 'storage', 'executionContexts'],
                    'invalidate_session' => true,
                    'enable_csrf' => true,
                ],
                'login_throttling' => [
                    'max_attempts' => 5,
                    'interval' => '15 minutes',
                ],
                'two_factor' => [
                    'check_path' => '2fa_login_check',
                    'auth_form_path' => '2fa_login',
                    'always_use_default_target_path' => true,
                    'enable_csrf' => true,
                ],
            ],
        ],
        'access_control' => [
            // Added by TwoFactorExtension::configureSecurity() before the explicit rules below.
            [
                'path' => '^/2fa',
                'roles' => ['IS_AUTHENTICATED_2FA_IN_PROGRESS'],
            ],
            [
                'path' => '^(?:' .
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
                    ')',
                'roles' => ['PUBLIC_ACCESS'],
            ],
            [
                'path' => '^/',
                'roles' => ['ROLE_USER'],
            ],
            [
                'path' => '^/2fa',
                'roles' => ['IS_AUTHENTICATED_2FA_IN_PROGRESS'],
            ],
        ],
        'role_hierarchy' => [
            'ROLE_ADMIN' => [],
            'ROLE_SUPER_ADMIN' => ['ROLE_ADMIN', 'ROLE_ALLOWED_TO_SWITCH'],
            'ROLE_CLIENT' => ['ROLE_USER'],
            'ROLE_USER' => [],
        ],
    ],
]);
