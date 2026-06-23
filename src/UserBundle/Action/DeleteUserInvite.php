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
use SolidInvoice\UserBundle\Repository\UserInvitationRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Uid\Ulid;

/**
 * @see \SolidInvoice\UserBundle\Tests\Action\DeleteUserInviteTest
 */
final readonly class DeleteUserInvite
{
    public function __construct(
        private UserInvitationRepository $invitationRepository,
        private RouterInterface $router
    ) {
    }

    public function __invoke(string $id): RedirectResponse
    {
        if (Ulid::isValid($id)) {
            // deleteInvitations() loads each invitation through a company-scoped
            // query, so only invitations belonging to the current company are removed.
            $this->invitationRepository->deleteInvitations([$id]);
        }

        $route = $this->router->generate('_users_list');

        return new class($route) extends RedirectResponse implements FlashResponse {
            public function getFlash(): Generator
            {
                yield FlashResponse::FLASH_SUCCESS => 'users.invitation.delete.success';
            }
        };
    }
}
