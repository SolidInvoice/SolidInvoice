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
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\UserBundle\Entity\ApiToken;
use SolidInvoice\UserBundle\Entity\ApiTokenHistory;
use SolidInvoice\UserBundle\Repository\ApiTokenHistoryRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Symfony\Contracts\Translation\TranslatorInterface;

class ApiTokenAuthenticator extends AbstractGuardAuthenticator
{
    public function __construct(
        protected ApiTokenUserProvider $userProvider,
        private readonly ManagerRegistry $registry,
        private readonly TranslatorInterface $translator,
        private readonly CompanySelector $companySelector
    ) {
    }

    public function supports(Request $request): bool
    {
        return $request->headers->has('X-API-TOKEN') || $request->query->has('token');
    }

    public function getCredentials(Request $request): string
    {
        return $request->headers->get('X-API-TOKEN', $request->query->get('token'));
    }

    /**
     * @param string $credentials
     */
    public function getUser($credentials, UserProviderInterface $userProvider): ?UserInterface
    {
        assert($userProvider instanceof ApiTokenUserProvider);

        $username = $userProvider->getUsernameForToken($credentials);

        if (! $username) {
            return null;
        }

        return $userProvider->loadUserByUsername($username);
    }

    public function checkCredentials($credentials, UserInterface $user): bool
    {
        // In case of an API token, no credential check is needed.
        // Return `true` to cause authentication success
        return true;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $providerKey): ?Response
    {
        $apiToken = $this->getCredentials($request);

        $history = new ApiTokenHistory();

        $history->setMethod($request->getMethod())
            ->setIp($request->getClientIp())
            ->setRequestData($request->request->all())
            ->setUserAgent($request->server->get('HTTP_USER_AGENT'))
            ->setResource($request->getPathInfo());

        /** @var ApiTokenHistoryRepository $repository */
        $repository = $this->registry->getRepository(ApiTokenHistory::class);

        $repository->addHistory($history, $apiToken);

        $apiToken = $this->registry->getRepository(ApiToken::class)->findOneBy(['token' => $apiToken]);

        if (null !== $apiToken) {
            $this->companySelector->switchCompany($apiToken->getCompany()->getId());
        }

        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $data = [
            'message' => $this->translator->trans($exception->getMessageKey(), $exception->getMessageData())
        ];

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }

    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        $data = [
            // @TODO: translate this message
            'message' => 'Authentication Required'
        ];

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }

    public function supportsRememberMe(): bool
    {
        return false;
    }
}
