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

namespace SolidInvoice\InvoiceBundle\Email;

use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\CoreBundle\Contracts\ClientEmailInterface;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\MailerBundle\Email\MessageSubjectParametersInterface;
use SolidInvoice\PaymentBundle\Entity\Payment;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;

/**
 * @see \SolidInvoice\InvoiceBundle\Tests\Functional\PaymentReceiptTest
 */
final class PaymentReceiptEmail extends TemplatedEmail implements ClientEmailInterface, MessageSubjectParametersInterface
{
    public function __construct(
        private readonly Payment $payment,
    ) {
        parent::__construct();

        /** @var Invoice $invoice */
        $invoice = $payment->getInvoice();

        $this->subject('payment.receipt.subject');
        $this->htmlTemplate('@SolidInvoiceInvoice/Email/receipt.html.twig');
        $this->textTemplate('@SolidInvoiceInvoice/Email/receipt.txt.twig');
        $this->context(['payment' => $payment, 'invoice' => $invoice]);
        $this->to(...$invoice->getUsers()->map(static fn (Contact $user): Address => new Address((string) $user->getEmail(), trim(sprintf('%s %s', $user->getFirstName(), $user->getLastName()))))->toArray());
    }

    public function getMessageSubjectParameters(): array
    {
        return ['%number%' => $this->payment->getReceiptNumber()];
    }

    public function getPayment(): Payment
    {
        return $this->payment;
    }
}
