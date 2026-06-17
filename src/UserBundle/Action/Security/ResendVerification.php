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

use Psr\Log\LoggerInterface;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Security\EmailVerifier;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;

/**
 * Re-sends the email-verification link for the currently authenticated user.
 *
 * Reachable from the {@see VerifyEmailNotice} sandbox page. Rate-limited per
 * user so the mailer cannot be used to spam an address.
 */
final class ResendVerification extends AbstractController
{
    public function __construct(
        private readonly Security $security,
        private readonly EmailVerifier $emailVerifier,
        #[Autowire(service: 'limiter.verification_resend')]
        private readonly RateLimiterFactory $limiter,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(Request $request): RedirectResponse
    {
        $user = $this->security->getUser();

        if (! $user instanceof User) {
            return $this->redirectToRoute('_login_main');
        }

        if ($user->isVerified()) {
            return $this->redirectToRoute('_dashboard');
        }

        if (! $this->isCsrfTokenValid('resend_verification', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid request. Please try again.');

            return $this->redirectToRoute('_verify_email_notice');
        }

        if (! $this->limiter->create($user->getUserIdentifier())->consume()->isAccepted()) {
            $this->addFlash('error', 'You have requested too many verification emails. Please wait a while before trying again.');

            return $this->redirectToRoute('_verify_email_notice');
        }

        try {
            $this->emailVerifier->sendEmailConfirmation(
                '_verify_email',
                $user,
                new TemplatedEmail()
                    ->to($user->getEmail())
                    ->subject('Please Confirm your Email')
                    ->htmlTemplate('@SolidInvoiceUser/Email/confirm_email.html.twig')
            );

            $this->addFlash('success', 'A new verification email has been sent. Please check your inbox.');
        } catch (TransportExceptionInterface $e) {
            $this->logger->error('Failed to resend email confirmation', ['exception' => $e]);
            $this->addFlash('error', 'We could not send the verification email. Please try again later.');
        }

        return $this->redirectToRoute('_verify_email_notice');
    }
}
