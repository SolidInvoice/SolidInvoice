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

namespace SolidInvoice\UserBundle\UserInvitation;

use SolidInvoice\UserBundle\Email\UserInvitationEmail;
use SolidInvoice\UserBundle\Entity\UserInvitation as UserInvitationEntity;
use Symfony\Component\Mailer\MailerInterface;

final readonly class UserInvitation
{
    public function __construct(
        private MailerInterface $mailer,
    ) {
    }

    public function sendUserInvitation(UserInvitationEntity $invitation): void
    {
        $this->send(new UserInvitationEmail($invitation), $invitation);
    }

    public function sendExpiryReminder(UserInvitationEntity $invitation): void
    {
        $this->send(new UserInvitationEmail($invitation, expiryReminder: true), $invitation);
    }

    private function send(UserInvitationEmail $mail, UserInvitationEntity $invitation): void
    {
        $mail->to($invitation->getEmail())
            ->from($invitation->getInvitedBy()?->getEmail());

        $this->mailer->send($mail);
    }
}
