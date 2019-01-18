<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\InvoiceBundle\Action;

use SolidInvoice\CoreBundle\Pdf\Generator;
use SolidInvoice\CoreBundle\Response\PdfResponse;
use SolidInvoice\CoreBundle\Templating\Template;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\PaymentBundle\Repository\PaymentRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Templating\EngineInterface;

final class View
{
    /**
     * @var PaymentRepository
     */
    private $paymentRepository;

    /**
     * @var Generator
     */
    private $pdfGenerator;

    /**
     * @var EngineInterface
     */
    private $engine;

    public function __construct(PaymentRepository $paymentRepository, Generator $pdfGenerator, EngineInterface $engine)
    {
        $this->paymentRepository = $paymentRepository;
        $this->pdfGenerator = $pdfGenerator;
        $this->engine = $engine;
    }

    /**
     * @param Request $request
     * @param Invoice $invoice
     *
     * @return Template|PdfResponse
     *
     * @throws \Mpdf\MpdfException|\RuntimeException|\InvalidArgumentException
     */
    public function __invoke(Request $request, Invoice $invoice)
    {
        if ('pdf' === $request->getRequestFormat()) {
            return new PdfResponse($this->pdfGenerator->generate($this->engine->render('@SolidInvoiceInvoice/Pdf/invoice.html.twig', ['invoice' => $invoice])), "invoice_{$invoice->getId()}.pdf");
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
