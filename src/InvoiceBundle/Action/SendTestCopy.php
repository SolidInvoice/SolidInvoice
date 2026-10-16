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

namespace SolidInvoice\InvoiceBundle\Action;

use SolidInvoice\InvoiceBundle\Email\InvoiceTestCopyEmail;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\UserBundle\Entity\User;
use SolidWorx\Platform\PlatformBundle\Controller\Security\VerifyEmail;
use SolidWorx\Platform\PlatformBundle\Security\EmailVerification\EmailVerificationGateInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

/**
 * "Email me a test copy": sends the invoice to the unverified user's own address, with a signed
 * link — built the way the platform's EmailVerifier builds it — that confirms the address and
 * brings them back to this invoice, where Send is then enabled.
 *
 * @see \SolidInvoice\InvoiceBundle\Tests\Functional\SendTestCopyTest
 */
final class SendTestCopy extends AbstractController
{
    use TargetPathTrait;

    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly EmailVerificationGateInterface $emailVerificationGate,
        private readonly TranslatorInterface $translator,
        private readonly RateLimiterFactoryInterface $invoiceTestCopyLimiter,
        // Only registered while email verification is on, which is the only time this action does anything.
        private readonly ?VerifyEmailHelperInterface $verifyEmailHelper = null,
    ) {
    }

    public function __invoke(Request $request, Invoice $invoice): RedirectResponse
    {
        $invoiceUrl = $this->generateUrl('_invoices_view', ['id' => $invoice->getId()]);
        $user = $this->getUser();

        if (! $this->isCsrfTokenValid('send_test_copy', $request->request->getString('_token'))) {
            $this->addFlash('error', 'invoice.send.invalid_csrf');

            return $this->redirect($invoiceUrl);
        }

        if (! $user instanceof User || ! $this->emailVerificationGate->isGated() || ! $this->verifyEmailHelper instanceof VerifyEmailHelperInterface) {
            return $this->redirect($invoiceUrl);
        }

        if (! $this->invoiceTestCopyLimiter->create($user->getId()->toString())->consume()->isAccepted()) {
            $this->addFlash('warning', 'invoice.test_copy.too_many');

            return $this->redirect($invoiceUrl);
        }

        $signature = $this->verifyEmailHelper->generateSignature(
            VerifyEmail::ROUTE_NAME,
            $user->getId()->toString(),
            (string) $user->getEmail(),
            ['id' => $user->getId()->toBase58()],
        );

        $email = new InvoiceTestCopyEmail($invoice, $signature->getSignedUrl())
            ->to((string) $user->getEmail())
            ->subject($this->translator->trans('invoice.test_copy.subject', ['%id%' => $invoice->getInvoiceId()], 'email'));

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface) {
            $this->addFlash('error', 'invoice.email.send_failed');

            return $this->redirect($invoiceUrl);
        }

        // The verify link signs the user in and lands on company selection, which honours this.
        $this->saveTargetPath($request->getSession(), 'main', $invoiceUrl);
        $this->addFlash('success', 'invoice.test_copy.sent');

        return $this->redirect($invoiceUrl);
    }
}
