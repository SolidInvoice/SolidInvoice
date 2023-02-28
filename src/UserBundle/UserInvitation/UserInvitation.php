<?php
declare(strict_types=1);

namespace SolidInvoice\UserBundle\UserInvitation;

use SolidInvoice\UserBundle\Entity\UserInvitation as UserInvitationEntity;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

final class UserInvitation
{
    private MailerInterface $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
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
