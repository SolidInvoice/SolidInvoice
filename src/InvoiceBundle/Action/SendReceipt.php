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

use Psr\Log\LoggerInterface;
use SolidInvoice\InvoiceBundle\Manager\ReceiptManager;
use SolidInvoice\PaymentBundle\Entity\Payment;
use SolidWorx\Platform\PlatformBundle\Security\EmailVerification\EmailVerificationGateInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

/**
 * @see \SolidInvoice\InvoiceBundle\Tests\Functional\PaymentReceiptTest
 */
final class SendReceipt extends AbstractController
{
    public function __construct(
        private readonly ReceiptManager $receiptManager,
        private readonly EmailVerificationGateInterface $emailVerificationGate,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(Request $request, Payment $payment): RedirectResponse
    {
        $response = $this->redirectToRoute('_invoices_payment_view', ['id' => $payment->getId()]);

        if (! $this->isCsrfTokenValid('send_receipt', $request->request->getString('_token'))) {
            $this->addFlash('error', 'invoice.send.invalid_csrf');

            return $response;
        }

        if ($this->emailVerificationGate->isGated()) {
            $this->addFlash('error', 'email_verification.flash.send_receipt');

            return $response;
        }

        try {
            $sent = $this->receiptManager->send($payment);
        } catch (TransportExceptionInterface $e) {
            $this->logger->error('Failed to send payment receipt', ['payment_id' => (string) $payment->getId(), 'exception' => $e]);
            $this->addFlash('error', 'payment.receipt.flash.send_failed');

            return $response;
        }

        $this->addFlash($sent ? 'success' : 'error', $sent ? 'payment.receipt.flash.sent' : 'payment.receipt.flash.no_contacts');

        return $response;
    }
}
