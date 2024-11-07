<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\UserBundle\Twig\Components;

use Doctrine\Common\Collections\Collection;
use InvalidArgumentException;
use SolidInvoice\UserBundle\Entity\ApiToken;
use SolidInvoice\UserBundle\Entity\ApiTokenHistory as ApiTokenHistoryEntity;
use SolidInvoice\UserBundle\Repository\ApiTokenRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsLiveComponent]
final class ApiTokenHistory extends AbstractController
{
    use DefaultActionTrait;

    #[LiveProp(writable: true)]
    public ?string $token = null;

    public function __construct(
        private readonly ApiTokenRepository $apiTokenRepository
    ) {
    }

    /**
     * @return Collection<int, ApiTokenHistoryEntity>
     */
    #[ExposeInTemplate]
    public function history(): Collection
    {
        return $this->apiToken()->getHistory();
    }

    private function apiToken(): ApiToken
    {
        if (! $this->token) {
            throw new InvalidArgumentException('Token ID is empty');
        }

        return $this->apiTokenRepository->find($this->token);
    }
}
