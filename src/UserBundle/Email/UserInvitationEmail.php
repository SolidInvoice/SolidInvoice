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

namespace SolidInvoice\UserBundle\Email;

use SolidInvoice\MailerBundle\Email\MessageSubjectParametersInterface;
use SolidInvoice\UserBundle\Entity\UserInvitation;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

final class UserInvitationEmail extends TemplatedEmail implements MessageSubjectParametersInterface
{
    public function __construct(
        private readonly UserInvitation $invitation,
        bool $expiryReminder = false,
    ) {
        parent::__construct();

        $this->subject($expiryReminder ? 'invitation.reminder.title' : 'invitation.title');
        $this->htmlTemplate($expiryReminder ? '@SolidInvoiceUser/Email/invitation_reminder.html.twig' : '@SolidInvoiceUser/Email/invitation.html.twig');
        $this->context(['invitation' => $this->invitation]);
    }

    public function getMessageSubjectParameters(): array
    {
        return ['%company%' => $this->invitation->getCompany()->getName()];
    }
}
