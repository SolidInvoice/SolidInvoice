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

namespace SolidInvoice\UserBundle\Security\OAuth;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use SensitiveParameter;
use SolidInvoice\UserBundle\Action\Security\OAuthConnectCheck;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\OAuth\GoogleIdentity;
use SolidInvoice\UserBundle\OAuth\GoogleUserProvisionerInterface;
use SolidInvoice\UserBundle\OAuth\OAuthUser;
use SolidWorx\Toggler\ToggleInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

/**
 * @see \SolidInvoice\UserBundle\Tests\Security\OAuth\OAuthAuthenticatorTest
 */
final class OAuthAuthenticator extends OAuth2Authenticator implements AuthenticationEntryPointInterface
{
    public function __construct(
        private readonly ClientRegistry $clientRegistry,
        private readonly RouterInterface $router,
        private readonly ToggleInterface $toggle,
        private readonly Security $security,
        private readonly GoogleUserProvisionerInterface $provisioner,
    ) {
    }

    public function supports(Request $request): bool
    {
        return $request->attributes->get('_route') === OAuthConnectCheck::ROUTE &&
            $this->toggle->isActive($request->attributes->get('service') . '_oauth_login');
    }

    public function authenticate(Request $request): Passport
    {
        $client = $this->clientRegistry->getClient($request->attributes->get('service'));
        $accessToken = $this->fetchAccessToken($client);

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function () use ($accessToken, $client): ?User {

                $oauthUser = new OAuthUser($client->fetchUserFromToken($accessToken));

                $identity = new GoogleIdentity(
                    googleId: $oauthUser->getId(),
                    email: (string) $oauthUser->getEmail(),
                    emailVerified: $oauthUser->getEmailVerified(),
                    firstName: $oauthUser->getFirstName() ?: null,
                    lastName: $oauthUser->getLastName() ?: null,
                );

                $currentUser = $this->security->getUser();
                $currentUser = $currentUser instanceof User ? $currentUser : null;

                return $this->provisioner->findOrCreate($identity, $currentUser)?->user;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, #[SensitiveParameter] TokenInterface $token, string $firewallName): ?Response
    {
        $targetUrl = $this->router->generate('_select_company');

        return new RedirectResponse($targetUrl);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $message = strtr($exception->getMessageKey(), $exception->getMessageData());

        /** @var Session $session */
        $session = $request->getSession();
        $session->getFlashBag()->add('error', $message);

        return new RedirectResponse(
            $this->router->generate('_login_main'),
        );
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return new RedirectResponse($this->router->generate('_login_main'));
    }
}
