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
use SolidInvoice\McpBundle\Entity\McpAccessToken;
use SolidInvoice\McpBundle\Entity\McpRefreshToken;
use SolidInvoice\McpBundle\Entity\OAuthClient;
use SolidInvoice\McpBundle\Repository\OAuthClientRepository;
use SolidInvoice\UserBundle\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Uid\Ulid;

#[Route(path: '/profile/connected-apps/{id}/revoke', name: 'mcp_connected_apps_revoke', methods: ['POST'])]
final class ConnectedAppsRevoke
{
    public function __construct(
        private readonly OAuthClientRepository $clientRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly Security $security,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function __invoke(string $id): Response
    {
        $user = $this->security->getUser();

        if (! $user instanceof User) {
            return new RedirectResponse('/login');
        }

        if (! Ulid::isValid($id)) {
            return new RedirectResponse($this->urlGenerator->generate('mcp_connected_apps_list'));
        }

        $client = $this->clientRepository->findOneBy(['id' => Ulid::fromString($id)]);

        if (! $client instanceof OAuthClient) {
            return new RedirectResponse($this->urlGenerator->generate('mcp_connected_apps_list'));
        }

        $this->entityManager->createQueryBuilder()
            ->update(McpAccessToken::class, 't')
            ->set('t.revoked', 'true')
            ->where('t.oauthClient = :client AND t.user = :user')
            ->setParameter('client', $client)
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();

        $this->entityManager->createQueryBuilder()
            ->update(McpRefreshToken::class, 'r')
            ->set('r.revoked', 'true')
            ->where('r.accessToken IN (
                SELECT t.id FROM ' . McpAccessToken::class . ' t
                WHERE t.oauthClient = :client AND t.user = :user
            )')
            ->setParameter('client', $client)
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();

        return new RedirectResponse($this->urlGenerator->generate('mcp_connected_apps_list'));
    }
}
