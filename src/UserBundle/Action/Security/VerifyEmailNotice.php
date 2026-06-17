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

namespace SolidInvoice\UserBundle\Action\Security;

use SolidInvoice\UserBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;

/**
 * "Please verify your email" landing page shown to unverified hosted users who
 * are sandboxed away from the rest of the application by
 * {@see \SolidInvoice\UserBundle\EventSubscriber\UnverifiedUserSubscriber}.
 */
final class VerifyEmailNotice extends AbstractController
{
    public function __construct(
        private readonly Security $security,
    ) {
    }

    public function __invoke(): Response
    {
        $user = $this->security->getUser();

        // Anonymous visitors have nothing to verify; send them to log in first.
        if (! $user instanceof User) {
            return $this->redirectToRoute('_login_main');
        }

        // Already verified users have no reason to be here.
        if ($user->isVerified()) {
            return $this->redirectToRoute('_dashboard');
        }

        return $this->render('@SolidInvoiceUser/Security/verify_email_notice.html.twig', [
            'email' => $user->getEmail(),
        ]);
    }
}
