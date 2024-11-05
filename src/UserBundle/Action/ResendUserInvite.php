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

namespace SolidInvoice\UserBundle\Action;

use Generator;
use SolidInvoice\CoreBundle\Response\FlashResponse;
use SolidInvoice\UserBundle\Entity\UserInvitation as UserInvitationEntity;
use SolidInvoice\UserBundle\Repository\UserInvitationRepository;
use SolidInvoice\UserBundle\UserInvitation\UserInvitation;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Uid\Ulid;

final class ResendUserInvite
{
    public function __construct(
        private readonly UserInvitation $invitation,
        private readonly UserInvitationRepository $invitationRepository,
        private readonly RouterInterface $router
    ) {
    }

    public function __invoke(string $id): RedirectResponse
    {
        $invitation = $this->invitationRepository->find(Ulid::fromString($id));

        if ($invitation instanceof UserInvitationEntity) {
            $this->invitation->sendUserInvitation($invitation);
        }

        $route = $this->router->generate('_users_list');

        return new class($route) extends RedirectResponse implements FlashResponse {
            public function getFlash(): Generator
            {
                yield FlashResponse::FLASH_SUCCESS => 'users.invitation.success';
            }
        };
    }
}
