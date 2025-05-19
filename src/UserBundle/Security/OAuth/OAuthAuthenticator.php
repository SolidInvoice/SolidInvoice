<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\UserBundle\Security\OAuth;

use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use SolidInvoice\UserBundle\Action\Security\OAuthConnectCheck;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\OAuth\OAuthUser;
use SolidWorx\Toggler\ToggleInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

final class OAuthAuthenticator extends OAuth2Authenticator implements AuthenticationEntryPointInterface
{
    public function __construct(
        private readonly ClientRegistry $clientRegistry,
        private readonly EntityManagerInterface $entityManager,
        private readonly RouterInterface $router,
        private readonly ToggleInterface $toggle,
        private readonly PropertyAccessorInterface $propertyAccessor,
        private readonly Security $security,
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
            new UserBadge($accessToken->getToken(), function () use ($accessToken, $client) {

                $oauthUser = new OAuthUser($client->fetchUserFromToken($accessToken));

                $userRepository = $this->entityManager->getRepository(User::class);
                $existingUser = $userRepository->findOneBy([$oauthUser->getPropertyMap() => $oauthUser->getId()]);

                if ($existingUser instanceof User) {
                    return $existingUser;
                }

                $user = $userRepository->findOneBy(['email' => $oauthUser->getEmail()]);

                if (! $user instanceof User) {
                    $currentUser = $this->security->getUser();

                    if ($currentUser instanceof User) {
                        $user = $currentUser;
                    } else {
                        if (! $this->toggle->isActive('allow_registration')) {
                            return null;
                        }

                        $user = new User();
                        $user->setEmail($oauthUser->getEmail());
                        $user->setPassword(bin2hex(random_bytes(24))); // Generate a secure random password that won't be used
                        $user->setEnabled(true);
                        $user->setVerified($oauthUser->getEmailVerified());
                    }
                }

                $this->propertyAccessor->setValue($user, $oauthUser->getPropertyMap(), $oauthUser->getId());

                $this->entityManager->persist($user);
                $this->entityManager->flush();

                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
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
            $this->router->generate('_login'),
        );
    }

    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        return new RedirectResponse($this->router->generate('_login'));
    }
}
