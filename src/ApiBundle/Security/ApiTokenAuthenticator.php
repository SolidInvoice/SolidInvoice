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
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Contracts\Translation\TranslatorInterface;

class ApiTokenAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private readonly ApiTokenUserProvider $userProvider,
        private readonly ManagerRegistry $registry,
        private readonly TranslatorInterface $translator,
        private readonly CompanySelector $companySelector
    ) {
    }

    public function supports(Request $request): bool
    {
        return $request->headers->has('X-API-TOKEN') || $request->query->has('token');
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $apiToken = $request->headers->get('X-API-TOKEN', $request->query->get('token'));

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

    public function authenticate(Request $request): Passport
    {
        $apiToken = $request->headers->get('X-API-TOKEN', $request->query->get('token'));

        if (null === $apiToken) {
            // The token header was empty, authentication fails with HTTP Status
            // Code 401 "Unauthorized"
            throw new CustomUserMessageAuthenticationException('No API token provided');
        }

        $userIdentifier = $this->userProvider->getUsernameForToken($apiToken);

        if (! $userIdentifier) {
            throw new CustomUserMessageAuthenticationException('Invalid API token');
        }

        return new SelfValidatingPassport(new UserBadge($userIdentifier));
    }
}
