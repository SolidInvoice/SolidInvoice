<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\UserBundle\EventSubscriber;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Psr\Log\LoggerInterface;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Security\EmailVerifier;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

#[AsEntityListener(event: Events::prePersist, entity: User::class)]
final class UserEntitySubscriber
{
    public function __construct(
        private readonly EmailVerifier $emailVerifier,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function prePersist(User $user): void
    {
        if ($user->isVerified()) {
            return;
        }

        try {
            $this->emailVerifier->sendEmailConfirmation(
                '_verify_email',
                $user,
                (new TemplatedEmail())
                    ->to($user->getEmail())
                    ->subject('Please Confirm your Email')
                    ->htmlTemplate('@SolidInvoiceUser/Email/confirm_email.html.twig')
            );
        } catch (TransportExceptionInterface $e) {
            $this->logger->error('Failed to send email confirmation', [
                'exception' => $e,
            ]);
        }
    }
}
