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

use SolidInvoice\UserBundle\Entity\UserInvitation as UserInvitationEntity;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

final class UserInvitation
{
    public function __construct(
        private readonly MailerInterface $mailer
    ) {
    }

    public function sendUserInvitation(UserInvitationEntity $invitation): void
    {
        $mail = new TemplatedEmail();

        $mail->to($invitation->getEmail())
            ->from($invitation->getInvitedBy()->getEmail())
            ->subject(sprintf('Invitation to join %s', $invitation->getCompany()->getName()))
            ->htmlTemplate('@SolidInvoiceUser/Email/invitation.html.twig')
            ->context([
                'invitation' => $invitation,
            ]);

        $this->mailer->send($mail);
    }
}
