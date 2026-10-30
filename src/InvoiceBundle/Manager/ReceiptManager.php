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

namespace SolidInvoice\InvoiceBundle\Manager;

use Mpdf\MpdfException;
use SolidInvoice\ClientBundle\Repository\ClientRepository;
use SolidInvoice\CoreBundle\Pdf\Generator;
use SolidInvoice\InvoiceBundle\Email\PaymentReceiptEmail;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\PaymentBundle\Entity\Payment;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * Receipts are rendered from the payment on demand, never stored.
 *
 * @see \SolidInvoice\InvoiceBundle\Tests\Functional\PaymentReceiptTest
 */
final readonly class ReceiptManager
{
    public function __construct(
        private Generator $generator,
        private Environment $twig,
        private MailerInterface $mailer,
        private ClientRepository $clientRepository,
    ) {
    }

    public function canPrintPdf(): bool
    {
        return $this->generator->canPrintPdf();
    }

    /**
     * @throws MpdfException|LoaderError|RuntimeError|SyntaxError
     */
    public function pdf(Payment $payment): string
    {
        return $this->generator->generate($this->html($payment));
    }

    /**
     * The markup the PDF is made from.
     *
     * @throws LoaderError|RuntimeError|SyntaxError
     */
    public function html(Payment $payment): string
    {
        $this->clientRepository->initializeArchived($payment->getClient());

        return $this->twig->render('@SolidInvoiceInvoice/Pdf/receipt.html.twig', ['payment' => $payment, 'invoice' => $payment->getInvoice()]);
    }

    public static function filename(Payment $payment): string
    {
        return sprintf('receipt_%s.pdf', $payment->getReceiptNumber());
    }

    /**
     * Emails the receipt, with the PDF attached, to the invoice's contacts. False when there is no
     * receipt to send or nobody to send it to.
     *
     * @throws TransportExceptionInterface|MpdfException|LoaderError|RuntimeError|SyntaxError
     */
    public function send(Payment $payment): bool
    {
        $invoice = $payment->getInvoice();

        if (! $payment->hasReceipt() || ! $invoice instanceof Invoice || $invoice->getUsers()->isEmpty()) {
            return false;
        }

        $email = new PaymentReceiptEmail($payment);

        if ($this->canPrintPdf()) {
            $email->attach($this->pdf($payment), self::filename($payment), 'application/pdf');
        }

        $this->mailer->send($email);

        return true;
    }
}
