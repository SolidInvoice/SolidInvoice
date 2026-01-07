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

use SolidInvoice\UserBundle\Entity\ApiToken;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Repository\ApiTokenRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

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

    #[LiveAction]
    public function revoke(#[LiveArg] ApiToken $token): void
    {
        $currentUser = $this->security->getUser();

        if (! $currentUser instanceof User) {
            throw new AccessDeniedException('You must be logged in to revoke tokens');
        }

        $tokenUser = $token->getUser();
        assert($tokenUser instanceof User);

        if ($tokenUser->getId() !== $currentUser->getId()) {
            throw new AccessDeniedException('You cannot revoke tokens that do not belong to you');
        }

        $this->apiTokenRepository->revoke($token);
        $this->emit('api.token.revoked');
        $this->dispatchBrowserEvent('modal:close');
        $this->addFlash('success', 'API Token revoked successfully');
    }
}
