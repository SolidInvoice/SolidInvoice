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

namespace SolidInvoice\CoreBundle\Action\Api;

use Mpdf\MpdfException;
use SolidInvoice\ClientBundle\Repository\ClientRepository;
use SolidInvoice\CoreBundle\Pdf\Generator;
use SolidInvoice\CoreBundle\Response\PdfResponse;
use SolidInvoice\CoreBundle\Templates\BillingTemplateChannel;
use SolidInvoice\CoreBundle\Templates\BillingTemplateResolver;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\QuoteBundle\Entity\Quote;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use function hash;
use function preg_replace;
use function sprintf;

/**
 * Renders an invoice or quote as a PDF for the API. API Platform has already loaded `$data` through the
 * CompanyFilter, so a document of another company never reaches this point (404).
 *
 * The PDF is built exactly like the public view's (`ViewBilling`): the template comes from
 * {@see BillingTemplateResolver} on the PDF channel and goes through the same {@see Generator}.
 */
final readonly class BillingPdfAction
{
    public function __construct(
        private Generator $pdfGenerator,
        private Environment $twig,
        private BillingTemplateResolver $templateResolver,
        private ClientRepository $clientRepository,
    ) {
    }

    /**
     * @throws LoaderError|MpdfException|RuntimeError|SyntaxError
     */
    public function __invoke(Request $request, Invoice | Quote $data): Response
    {
        [$entity, $number] = $data instanceof Invoice ? ['invoice', $data->getInvoiceId()] : ['quote', $data->getQuoteId()];

        $etag = hash('xxh128', sprintf('%s|%s', $data->getId(), $data->getUpdated()?->format('U.u')));

        $notModified = new Response();
        $notModified->setPrivate();
        $notModified->setEtag($etag);

        if ($notModified->isNotModified($request)) {
            return $notModified;
        }

        if (! $this->pdfGenerator->canPrintPdf()) {
            throw new ServiceUnavailableHttpException();
        }

        $this->clientRepository->initializeArchived($data->getClient());

        $html = $this->twig->render($this->templateResolver->resolve($data, BillingTemplateChannel::Pdf), [$entity => $data]);

        // The document number is free text; keep the header ASCII and free of quotes and separators.
        $response = new PdfResponse($this->pdfGenerator->generate($html), sprintf('%s_%s.pdf', $entity, preg_replace('/[^A-Za-z0-9._-]/', '_', $number)));
        $response->setPrivate();
        $response->setEtag($etag);

        return $response;
    }
}
