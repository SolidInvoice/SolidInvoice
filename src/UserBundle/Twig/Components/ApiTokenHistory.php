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

use Doctrine\ORM\EntityManagerInterface;
use SolidInvoice\UserBundle\Entity\ApiToken;
use SolidInvoice\UserBundle\Repository\ApiTokenHistoryRepository;
use Symfony\Component\Uid\Ulid;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsTwigComponent]
final class ApiTokenHistory
{
    public ?string $token = null;

    public function __construct(
        private readonly ApiTokenHistoryRepository $historyRepository,
        private readonly EntityManagerInterface $entityManager,
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

        $apiToken = $this->entityManager->find(ApiToken::class, Ulid::fromString($this->token));

        if (null === $apiToken) {
            return [];
        }

        return iterator_to_array($this->historyRepository->getHistoryForToken($apiToken));
    }
}
