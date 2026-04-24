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
use SolidInvoice\UserBundle\Entity\User;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

#[Route(path: '/profile/connected-apps', name: 'mcp_connected_apps_list', methods: ['GET'])]
final class ConnectedAppsList
{
    public function __construct(
        private readonly McpAccessTokenRepository $accessTokenRepository,
        private readonly Security $security,
        private readonly Environment $twig,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function __invoke(): Response
    {
        $user = $this->security->getUser();

        if (! $user instanceof User) {
            return new RedirectResponse($this->urlGenerator->generate('_login_main'));
        }

        $tokens = $this->accessTokenRepository->createQueryBuilder('t')
            ->select('t, c, co')
            ->join('t.oauthClient', 'c')
            ->join('t.company', 'co')
            ->andWhere('t.user = :user')
            ->andWhere('t.revoked = :revoked')
            ->setParameter('user', $user->getId(), UlidType::NAME)
            ->setParameter('revoked', false)
            ->orderBy('t.created', 'DESC')
            ->getQuery()
            ->getResult();

        // Group by (client, company) — the same OAuth client can be authorised
        // for multiple tenants, and each pair is revoked independently.
        /** @var array<string, array{client: \SolidInvoice\McpBundle\Entity\OAuthClient, scopes: list<string>, company: \SolidInvoice\CoreBundle\Entity\Company, last_used: \DateTimeInterface|null, token_count: int}> $byClient */
        $byClient = [];

        foreach ($tokens as $token) {
            $company = $token->getCompany();
            $groupKey = $token->getOAuthClient()->getIdentifier() . ':' . ($company->getId()?->toRfc4122() ?? '');

            if (! isset($byClient[$groupKey])) {
                $byClient[$groupKey] = [
                    'client' => $token->getOAuthClient(),
                    'scopes' => $token->getScopeValues(),
                    'company' => $company,
                    'last_used' => $token->getLastUsedAt() ?? $token->getCreated(),
                    'token_count' => 0,
                ];
            } else {
                $tokenLastUsed = $token->getLastUsedAt() ?? $token->getCreated();
                $current = $byClient[$groupKey]['last_used'];

                if ($tokenLastUsed !== null && ($current === null || $tokenLastUsed > $current)) {
                    $byClient[$groupKey]['last_used'] = $tokenLastUsed;
                }
            }

            ++$byClient[$groupKey]['token_count'];
        }

        return new Response(
            $this->twig->render('@SolidInvoiceMcp/ConnectedApps/index.html.twig', [
                'connected_apps' => array_values($byClient),
            ]),
        );
    }
}
