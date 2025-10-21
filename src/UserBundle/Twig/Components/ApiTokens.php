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

use DateTimeInterface;
use SolidInvoice\UserBundle\Entity\ApiToken;
use SolidInvoice\UserBundle\Repository\ApiTokenRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Uid\Ulid;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsLiveComponent]
final class ApiTokens extends AbstractController
{
    use DefaultActionTrait;
    use ComponentToolsTrait;

    public function __construct(
        private readonly ApiTokenRepository $apiTokenRepository,
        private readonly Security $security,
    ) {
    }

    /**
     * @return array{id: Ulid, name: string, token: string, created: DateTimeInterface, updated: DateTimeInterface, lastUsed: DateTimeInterface}
     */
    #[ExposeInTemplate]
    #[LiveListener(CreateApiToken::API_TOKEN_CREATED_EVENT)]
    public function apiTokens(): array
    {
        return $this->apiTokenRepository->getApiTokensForUser($this->security->getUser());
    }

    #[LiveAction]
    public function revoke(#[LiveArg] ApiToken $token): void
    {
        $this->apiTokenRepository->revoke($token);
        $this->dispatchBrowserEvent('modal:close');
    }
}
