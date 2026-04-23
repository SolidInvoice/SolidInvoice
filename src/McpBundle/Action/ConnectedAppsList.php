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

namespace SolidInvoice\McpBundle\Action;

use SolidInvoice\McpBundle\Repository\McpAccessTokenRepository;
use SolidInvoice\McpBundle\Repository\OAuthClientRepository;
use SolidInvoice\UserBundle\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;

#[Route(path: '/profile/connected-apps', name: 'mcp_connected_apps_list', methods: ['GET'])]
final class ConnectedAppsList
{
    public function __construct(
        private readonly OAuthClientRepository $clientRepository,
        private readonly McpAccessTokenRepository $accessTokenRepository,
        private readonly Security $security,
        private readonly Environment $twig,
    ) {
    }

    public function __invoke(): Response
    {
        $user = $this->security->getUser();

        if (! $user instanceof User) {
            return new RedirectResponse('/login');
        }

        $tokens = $this->accessTokenRepository->createQueryBuilder('t')
            ->andWhere('t.user = :user')
            ->andWhere('t.revoked = :revoked')
            ->setParameter('user', $user)
            ->setParameter('revoked', false)
            ->orderBy('t.created', 'DESC')
            ->getQuery()
            ->getResult();

        /** @var array<string, array{client: \SolidInvoice\McpBundle\Entity\OAuthClient, scopes: list<string>, company: \SolidInvoice\CoreBundle\Entity\Company, last_used: \DateTimeInterface|null, token_count: int}> $byClient */
        $byClient = [];

        foreach ($tokens as $token) {
            $clientId = $token->getOAuthClient()->getIdentifier();

            if (! isset($byClient[$clientId])) {
                $byClient[$clientId] = [
                    'client' => $token->getOAuthClient(),
                    'scopes' => $token->getScopeValues(),
                    'company' => $token->getCompany(),
                    'last_used' => $token->getLastUsedAt() ?? $token->getCreated(),
                    'token_count' => 0,
                ];
            } else {
                $tokenLastUsed = $token->getLastUsedAt() ?? $token->getCreated();
                $current = $byClient[$clientId]['last_used'];

                if ($tokenLastUsed !== null && ($current === null || $tokenLastUsed > $current)) {
                    $byClient[$clientId]['last_used'] = $tokenLastUsed;
                }
            }

            ++$byClient[$clientId]['token_count'];
        }

        return new Response(
            $this->twig->render('@SolidInvoiceMcp/ConnectedApps/index.html.twig', [
                'connected_apps' => array_values($byClient),
            ]),
        );
    }
}
