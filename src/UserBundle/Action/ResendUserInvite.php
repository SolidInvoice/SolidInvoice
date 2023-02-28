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
use Ramsey\Uuid\Uuid;
use SolidInvoice\CoreBundle\Response\FlashResponse;
use SolidInvoice\UserBundle\Entity\UserInvitation as UserInvitationEntity;
use SolidInvoice\UserBundle\Repository\UserInvitationRepository;
use SolidInvoice\UserBundle\UserInvitation\UserInvitation;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;

final class ResendUserInvite
{
    private UserInvitation $invitation;

    private UserInvitationRepository $invitationRepository;

    private RouterInterface $router;

    public function __construct(
        UserInvitation $invitation,
        UserInvitationRepository $invitationRepository,
        RouterInterface $router
    ) {
        $this->invitation = $invitation;
        $this->invitationRepository = $invitationRepository;
        $this->router = $router;
    }

    public function __invoke(string $id): RedirectResponse
    {
        $invitation = $this->invitationRepository->find(Uuid::fromString($id));

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
