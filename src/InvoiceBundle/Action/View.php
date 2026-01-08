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
use SolidInvoice\CoreBundle\Pdf\Generator;
use SolidInvoice\CoreBundle\Response\PdfResponse;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\PaymentBundle\Repository\PaymentRepository;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

final readonly class View
{
    public function __construct(
        private PaymentRepository $paymentRepository,
        private Generator $pdfGenerator,
        private Environment $twig
    ) {
    }

    /**
     * @return array{invoice: Invoice, payments: array<string, mixed>}|Response
     * @throws LoaderError
     * @throws MpdfException
     * @throws RuntimeError
     * @throws SyntaxError
     */
    #[Template('@SolidInvoiceInvoice/Default/view.html.twig')]
    public function __invoke(Request $request, Invoice $invoice): array | Response
    {
        if ('pdf' === $request->getRequestFormat() && $this->pdfGenerator->canPrintPdf()) {
            return new PdfResponse($this->pdfGenerator->generate($this->twig->render('@SolidInvoiceInvoice/Pdf/invoice.html.twig', ['invoice' => $invoice])), "invoice_{$invoice->getInvoiceId()}.pdf");
        }

        return [
            'invoice' => $invoice,
            'payments' => $this->paymentRepository->getPaymentsForInvoice($invoice),
        ];
    }
}
