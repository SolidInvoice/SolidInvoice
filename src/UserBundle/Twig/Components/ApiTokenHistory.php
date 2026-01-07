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

namespace SolidInvoice\UserBundle\Twig\Components;

use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Repository\ApiTokenHistoryRepository;
use SolidInvoice\UserBundle\Repository\ApiTokenRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Uid\Ulid;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsTwigComponent]
final class ApiTokenHistory
{
    public ?string $token = null;

    public function __construct(
        private readonly ApiTokenHistoryRepository $historyRepository,
        private readonly ApiTokenRepository $tokenRepository,
        private readonly Security $security,
    ) {
    }

    /**
     * @return list<\SolidInvoice\UserBundle\Entity\ApiTokenHistory>
     */
    #[ExposeInTemplate]
    public function getHistory(): array
    {
        if (null === $this->token) {
            return [];
        }

        $apiToken = $this->tokenRepository->find(Ulid::fromString($this->token));

        if (null === $apiToken) {
            return [];
        }

        // Verify token belongs to current user
        $currentUser = $this->security->getUser();
        if (! $currentUser instanceof User || $apiToken->getUser()->getId() !== $currentUser->getId()) {
            return [];
        }

        return iterator_to_array($this->historyRepository->getHistoryForToken($apiToken));
    }
}
