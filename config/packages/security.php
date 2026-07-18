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
use SolidInvoice\UserBundle\Security\LoginLinkSuccessHandler;
use SolidInvoice\UserBundle\Security\OAuth\OAuthAuthenticator;
use SolidInvoice\UserBundle\Security\UserChecker;
use SolidInvoice\UserBundle\Security\VerifiedUserChecker;
use SolidWorx\Platform\PlatformBundle\DependencyInjection\Extension\LoginExtension;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

// The platform's default `security` config (the `main` form-login firewall, the
// `User`/`platform_user` password hasher, remember-me, logout, login throttling
// and — when `platform.security.two_factor.enabled` is set in platform.yaml — the
// `two_factor` firewall block and the `^/2fa` access-control rules) comes from
// LoginExtension::defaultFormLoginConfig(). The app-specific settings below are
// deep-merged onto those defaults: associative arrays merge by key, scalars are
// overwritten, and sequential lists (access_control) are concatenated with the
// platform's values first.
$config = LoginExtension::defaultFormLoginConfig([
    'password_hashers' => [
        PasswordAuthenticatedUserInterface::class => [
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
        // Google One Tap verify/nonce endpoints are stateless and cross-origin;
        // they authenticate from the request body (the Google JWT + single-use
        // nonce), never from a session, so the firewall itself carries no
        // security. The `_onetap_login_check` route is deliberately excluded
        // from this pattern so it falls through to `main`, where the login_link
        // authenticator can establish the session. (Only present in the SaaS
        // build, where the `^/onetap/*` routes are registered.)
        'onetap' => [
            'pattern' => '^/onetap/(verify|nonce)$',
            'security' => false,
        ],
        'main' => [
            'user_checker' => UserChecker::class,
            'custom_authenticators' => [OAuthAuthenticator::class],
            'form_login' => [
                'default_target_path' => '_select_company',
            ],
            // Consumes the single-use login links minted by the Google One Tap
            // verify endpoint. Short-lived and single-use so a leaked link cannot
            // be reused. Inert outside the SaaS build (the check route does not
            // exist there, so the authenticator never matches a request).
            'login_link' => [
                'check_route' => '_onetap_login_check',
                'lifetime' => 60,
                'max_uses' => 1,
                'signature_properties' => ['id'],
                'success_handler' => LoginLinkSuccessHandler::class,
            ],
        ],
    ],
    'access_control' => [
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
                '/onetap/login-check$|' .
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
    ],
    'role_hierarchy' => [
        'ROLE_ADMIN' => [],
        'ROLE_SUPER_ADMIN' => ['ROLE_ADMIN', 'ROLE_ALLOWED_TO_SWITCH'],
        'ROLE_CLIENT' => ['ROLE_USER'],
        'ROLE_USER' => [],
    ],
]);

// The helper seeds the platform `main` firewall first, but Symfony matches firewalls
// top-to-bottom and `main` (pattern `^/`) is the catch-all: it must be evaluated last
// so the stateless/anonymous firewalls above (assets, api, mcp, install, …) match
// first. Move it to the end while keeping every value the helper merged into it.
$firewalls = $config['security']['firewalls'];
$main = $firewalls['main'];
unset($firewalls['main']);
$firewalls['main'] = $main;
$config['security']['firewalls'] = $firewalls;

return App::config($config);
