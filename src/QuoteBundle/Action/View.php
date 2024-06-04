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

namespace SolidInvoice\QuoteBundle\Action;

use Mpdf\MpdfException;
use SolidInvoice\CoreBundle\Pdf\Generator;
use SolidInvoice\CoreBundle\Response\PdfResponse;
use SolidInvoice\CoreBundle\Templating\Template;
use SolidInvoice\QuoteBundle\Entity\Quote;
use Symfony\Component\HttpFoundation\Request;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

final class View
{
    public function __construct(
        private readonly Generator $pdfGenerator,
        private readonly Environment $engine
    ) {
    }

    /**
     * @throws MpdfException|LoaderError|RuntimeError|SyntaxError
     */
    public function __invoke(Request $request, Quote $quote): Template | PdfResponse
    {
        if ('pdf' === $request->getRequestFormat() && $this->pdfGenerator->canPrintPdf()) {
            return new PdfResponse($this->pdfGenerator->generate($this->engine->render('@SolidInvoiceQuote/Pdf/quote.html.twig', ['quote' => $quote])), "quote_{$quote->getId()}.pdf");
        }

        return new Template('@SolidInvoiceQuote/Default/view.html.twig', ['quote' => $quote]);
    }
}
