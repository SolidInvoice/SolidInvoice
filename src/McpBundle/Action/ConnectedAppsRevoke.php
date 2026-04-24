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

use Doctrine\ORM\EntityManagerInterface;
use SolidInvoice\McpBundle\Entity\ConsentGrant;
use SolidInvoice\McpBundle\Entity\McpAccessToken;
use SolidInvoice\McpBundle\Entity\McpRefreshToken;
use SolidInvoice\McpBundle\Entity\OAuthClient;
use SolidInvoice\McpBundle\Repository\OAuthClientRepository;
use SolidInvoice\UserBundle\Entity\User;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Uid\Ulid;

#[Route(path: '/profile/connected-apps/{id}/revoke', name: 'mcp_connected_apps_revoke', methods: ['POST'])]
final class ConnectedAppsRevoke
{
    public const string CSRF_TOKEN_ID = 'mcp_connected_apps_revoke';

    public function __construct(
        private readonly OAuthClientRepository $clientRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly Security $security,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
    ) {
    }

    public function __invoke(Request $request, string $id): Response
    {
        $user = $this->security->getUser();

        if (! $user instanceof User) {
            return new RedirectResponse($this->urlGenerator->generate('_login_main'));
        }

        $csrfToken = (string) $request->request->get('_csrf_token', '');

        if (! $this->csrfTokenManager->isTokenValid(new CsrfToken(self::CSRF_TOKEN_ID, $csrfToken))) {
            throw new BadRequestHttpException('Invalid CSRF token.');
        }

        try {
            $ulid = Ulid::fromString($id);
        } catch (\InvalidArgumentException) {
            return new RedirectResponse($this->urlGenerator->generate('mcp_connected_apps_list'));
        }

        $client = $this->clientRepository->find($ulid);

        if (! $client instanceof OAuthClient) {
            return new RedirectResponse($this->urlGenerator->generate('mcp_connected_apps_list'));
        }

        $this->entityManager->createQueryBuilder()
            ->update(McpAccessToken::class, 't')
            ->set('t.revoked', 'true')
            ->where('t.oauthClient = :client AND t.user = :user')
            ->setParameter('client', $client->getId(), UlidType::NAME)
            ->setParameter('user', $user->getId(), UlidType::NAME)
            ->getQuery()
            ->execute();

        $this->entityManager->createQueryBuilder()
            ->update(McpRefreshToken::class, 'r')
            ->set('r.revoked', 'true')
            ->where('r.accessToken IN (
                SELECT t.id FROM ' . McpAccessToken::class . ' t
                WHERE t.oauthClient = :client AND t.user = :user
            )')
            ->setParameter('client', $client->getId(), UlidType::NAME)
            ->setParameter('user', $user->getId(), UlidType::NAME)
            ->getQuery()
            ->execute();

        // Also clear remembered consent so the same client can't silently mint
        // fresh tokens from a prior "don't ask me again" grant.
        $this->entityManager->createQueryBuilder()
            ->delete(ConsentGrant::class, 'g')
            ->where('g.client = :client AND g.user = :user')
            ->setParameter('client', $client->getId(), UlidType::NAME)
            ->setParameter('user', $user->getId(), UlidType::NAME)
            ->getQuery()
            ->execute();

        return new RedirectResponse($this->urlGenerator->generate('mcp_connected_apps_list'));
    }
}
