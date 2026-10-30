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

use Mpdf\MpdfException;
use SolidInvoice\CoreBundle\Response\PdfResponse;
use SolidInvoice\InvoiceBundle\Manager\ReceiptManager;
use SolidInvoice\PaymentBundle\Entity\Payment;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * @see \SolidInvoice\InvoiceBundle\Tests\Functional\PaymentReceiptTest
 */
final readonly class ReceiptPdf
{
    public function __construct(
        private ReceiptManager $receiptManager,
    ) {
    }

    /**
     * @param ResponseHeaderBag::DISPOSITION_* $disposition
     *
     * @throws MpdfException|LoaderError|RuntimeError|SyntaxError
     */
    public function __invoke(Payment $payment, string $disposition): PdfResponse
    {
        if (! $payment->hasReceipt() || ! $this->receiptManager->canPrintPdf()) {
            throw new NotFoundHttpException();
        }

        return new PdfResponse($this->receiptManager->pdf($payment), ReceiptManager::filename($payment), $disposition);
    }
}
