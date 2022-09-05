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

namespace SolidInvoice\ApiBundle\Security;

use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\ApiBundle\Security\Provider\ApiTokenUserProvider;
use SolidInvoice\UserBundle\Entity\ApiTokenHistory;
use SolidInvoice\UserBundle\Repository\ApiTokenHistoryRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\Authentication\SimplePreAuthenticatorInterface;

class ApiTokenAuthenticator implements SimplePreAuthenticatorInterface, AuthenticationSuccessHandlerInterface
{
    /**
     * @var ApiTokenUserProvider
     */
    protected $userProvider;

    /**
     * @var ManagerRegistry
     */
    private $registry;

    public function __construct(
        ApiTokenUserProvider $userProvider,
        ManagerRegistry $registry
    ) {
        $this->userProvider = $userProvider;
        $this->registry = $registry;
    }

    /**
     * @param string $providerKey
     *
     * @throws AuthenticationCredentialsNotFoundException
     */
    public function createToken(Request $request, $providerKey): PreAuthenticatedToken
    {
        $token = $this->getToken($request);

        if (! $token) {
            throw new AuthenticationCredentialsNotFoundException('No API token found');
            // when we allow other methods of authentication against the api, skip api key authentication by returning null
        }

        return new PreAuthenticatedToken(
            'anon.',
            $token,
            $providerKey
        );
    }

    /**
     * @return string
     */
    private function getToken(Request $request): ?string
    {
        return $request->headers->get('X-API-TOKEN', $request->query->get('token'));
    }

    /**
     * @param string $providerKey
     *
     * @throws BadCredentialsException
     */
    public function authenticateToken(TokenInterface $token, UserProviderInterface $userProvider, $providerKey): PreAuthenticatedToken
    {
        assert($userProvider instanceof ApiTokenUserProvider);

        $apiToken = $token->getCredentials();
        $username = $userProvider->getUsernameForToken($apiToken);

        if (! $username) {
            throw new BadCredentialsException(sprintf('API Token "%s" is invalid.', $apiToken));
        }

        $user = $userProvider->loadUserByUsername($username);

        $roles = array_merge(
            $user->getRoles(),
            [
                'ROLE_API_AUTHENTICATED',
            ]
        );

        return new PreAuthenticatedToken($user, $apiToken, $providerKey, $roles);
    }

    /**
     * @param string $providerKey
     */
    public function supportsToken(TokenInterface $token, $providerKey): bool
    {
        return $token instanceof PreAuthenticatedToken && $token->getProviderKey() === $providerKey;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token)
    {
        $apiToken = $this->getToken($request);

        $history = new ApiTokenHistory();

        $history->setMethod($request->getMethod())
            ->setIp($request->getClientIp())
            ->setRequestData($request->request->all())
            ->setUserAgent($request->server->get('HTTP_USER_AGENT'))
            ->setResource($request->getPathInfo());

        /** @var ApiTokenHistoryRepository $repository */
        $repository = $this->registry->getRepository(ApiTokenHistory::class);

        $repository->addHistory($history, $apiToken);

        return null;
    }
}
