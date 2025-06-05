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

use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class TwoFactor extends AbstractController
{
    use DefaultActionTrait;

    public function __construct(
        private readonly UserRepository $userRepository
    ) {
    }

    #[LiveAction()]
    public function enableEmailAuth(): void
    {
        $user = $this->getUser();
        if (! $user instanceof User) {
            return;
        }

        $user->enableEmailAuth(true);

        $this->userRepository->save($user);
    }

    #[LiveAction()]
    public function disableEmailAuth(): void
    {
        $user = $this->getUser();
        if (! $user instanceof User) {
            return;
        }

        $user->enableEmailAuth(false);

        $this->userRepository->save($user);
    }
}
