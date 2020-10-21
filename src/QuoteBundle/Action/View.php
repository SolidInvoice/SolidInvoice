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

namespace SolidInvoice\QuoteBundle\Action;

use InvalidArgumentException;
use Mpdf\MpdfException;
use RuntimeException;
use SolidInvoice\CoreBundle\Pdf\Generator;
use SolidInvoice\CoreBundle\Response\PdfResponse;
use SolidInvoice\CoreBundle\Templating\Template;
use SolidInvoice\QuoteBundle\Entity\Quote;
use Symfony\Component\HttpFoundation\Request;
use Twig\Environment;

final class View
{
    /**
     * @var Generator
     */
    private $pdfGenerator;

    /**
     * @var Environment
     */
    private $engine;

    public function __construct(Generator $pdfGenerator, Environment $twig)
    {
        $this->pdfGenerator = $pdfGenerator;
        $this->engine = $twig;
    }

    /**
     * @return Template|PdfResponse
     *
     * @throws MpdfException|RuntimeException|InvalidArgumentException
     */
    public function __invoke(Request $request, Quote $quote)
    {
        if ('pdf' === $request->getRequestFormat() && $this->pdfGenerator->canPrintPdf()) {
            return new PdfResponse($this->pdfGenerator->generate($this->engine->render('@SolidInvoiceQuote/Pdf/quote.html.twig', ['quote' => $quote])), "quote_{$quote->getId()}.pdf");
        }

        return new Template('@SolidInvoiceQuote/Default/view.html.twig', ['quote' => $quote]);
    }
}
