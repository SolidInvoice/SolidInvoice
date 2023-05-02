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

use InvalidArgumentException;
use Mpdf\MpdfException;
use RuntimeException;
use SolidInvoice\CoreBundle\Pdf\Generator;
use SolidInvoice\CoreBundle\Response\PdfResponse;
use SolidInvoice\CoreBundle\Templating\Template;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\PaymentBundle\Repository\PaymentRepository;
use Symfony\Component\HttpFoundation\Request;
use Twig\Environment;

final class View
{
    public function __construct(private readonly PaymentRepository $paymentRepository, private readonly Generator $pdfGenerator, private readonly Environment $twig)
    {
    }

    /**
     * @return Template|PdfResponse
     *
     * @throws MpdfException|RuntimeException|InvalidArgumentException
     */
    public function __invoke(Request $request, Invoice $invoice)
    {
        if ('pdf' === $request->getRequestFormat() && $this->pdfGenerator->canPrintPdf()) {
            return new PdfResponse($this->pdfGenerator->generate($this->twig->render('@SolidInvoiceInvoice/Pdf/invoice.html.twig', ['invoice' => $invoice])), "invoice_{$invoice->getId()}.pdf");
        }

        return new Template(
            '@SolidInvoiceInvoice/Default/view.html.twig',
            [
                'invoice' => $invoice,
                'payments' => $this->paymentRepository->getPaymentsForInvoice($invoice),
            ]
        );
    }
}
