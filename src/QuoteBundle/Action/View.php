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
use SolidInvoice\CoreBundle\Templates\BillingTemplateChannel;
use SolidInvoice\CoreBundle\Templates\BillingTemplateResolver;
use SolidInvoice\QuoteBundle\Entity\Quote;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Component\HttpFoundation\Request;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * @see \SolidInvoice\QuoteBundle\Tests\Action\ViewTest
 */
final readonly class View
{
    public function __construct(
        private Generator $pdfGenerator,
        private Environment $engine,
        private BillingTemplateResolver $templateResolver,
    ) {
    }

    /**
     * @return array{quote: Quote, documentTemplate: string|null}|PdfResponse
     * @throws MpdfException|LoaderError|RuntimeError|SyntaxError
     */
    #[Template('@SolidInvoiceQuote/Default/view.html.twig')]
    public function __invoke(Request $request, Quote $quote): array | PdfResponse
    {
        if ('pdf' === $request->getRequestFormat() && $this->pdfGenerator->canPrintPdf()) {
            return new PdfResponse($this->pdfGenerator->generate($this->engine->render($this->templateResolver->resolve($quote, BillingTemplateChannel::Pdf), ['quote' => $quote])), sprintf('quote_%s.pdf', $quote->getQuoteId()));
        }

        return [
            'quote' => $quote,
            'documentTemplate' => $this->templateResolver->customTemplate($quote, BillingTemplateChannel::View),
        ];
    }
}
