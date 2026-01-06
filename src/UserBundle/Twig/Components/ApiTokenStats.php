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

use DateTimeInterface;
use SolidInvoice\UserBundle\Repository\ApiTokenRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsLiveComponent]
final class ApiTokenStats extends AbstractController
{
    use DefaultActionTrait;

    public function __construct(
        private readonly ApiTokenRepository $apiTokenRepository,
        private readonly Security $security,
    ) {
    }

    /**
     * @return array{activeTokens: int, apiCallsThisMonth: int, lastActivity: ?DateTimeInterface, mostUsedToken: ?string}
     */
    #[ExposeInTemplate]
    #[LiveListener(CreateApiToken::API_TOKEN_CREATED_EVENT)]
    #[LiveListener('api.token.revoked')]
    public function stats(): array
    {
        $user = $this->security->getUser();

        if (! $user) {
            return [
                'activeTokens' => 0,
                'apiCallsThisMonth' => 0,
                'lastActivity' => null,
                'mostUsedToken' => null,
            ];
        }

        return [
            'activeTokens' => $this->apiTokenRepository->getActiveTokenCountForUser($user),
            'apiCallsThisMonth' => $this->apiTokenRepository->getApiCallsThisMonthForUser($user),
            'lastActivity' => $this->apiTokenRepository->getLastActivityForUser($user),
            'mostUsedToken' => $this->apiTokenRepository->getMostUsedTokenForUser($user),
        ];
    }
}
