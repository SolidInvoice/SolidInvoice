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

namespace SolidInvoice\CoreBundle\Action\BillingTemplate;

use InvalidArgumentException;
use Mpdf\MpdfException;
use SolidInvoice\CoreBundle\Entity\BillingTemplate;
use SolidInvoice\CoreBundle\Pdf\Generator;
use SolidInvoice\CoreBundle\Response\PdfResponse;
use SolidInvoice\CoreBundle\Sample\BillingSampleFactory;
use SolidInvoice\CoreBundle\Templating\BillingTemplateResolver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;
use Twig\Error\Error as TwigError;
use function in_array;
use function strtolower;

/**
 * Renders the requested billing template into a real PDF via mPDF so the
 * editor can show the user exactly what their PDF clients will receive.
 */
final class PreviewPdf extends AbstractController
{
    public function __construct(
        private readonly BillingTemplateResolver $resolver,
        private readonly BillingSampleFactory $sampleFactory,
        private readonly Environment $twig,
        private readonly Generator $pdfGenerator,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        if (! $this->pdfGenerator->canPrintPdf()) {
            return new JsonResponse(['error' => 'PDF support is not available'], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        $payload = json_decode((string) $request->getContent(), true);

        if (! is_array($payload)) {
            return new JsonResponse(['error' => 'Invalid JSON payload'], Response::HTTP_BAD_REQUEST);
        }

        $token = (string) ($payload['_token'] ?? '');
        if (! $this->isCsrfTokenValid('billing_template_preview', $token)) {
            return new JsonResponse(['error' => 'Invalid CSRF token'], Response::HTTP_FORBIDDEN);
        }

        $type = strtolower((string) ($payload['type'] ?? ''));
        $content = (string) ($payload['content'] ?? '');

        if (! in_array($type, [BillingTemplate::TYPE_INVOICE, BillingTemplate::TYPE_QUOTE], true)) {
            return new JsonResponse(['error' => 'Unsupported type'], Response::HTTP_BAD_REQUEST);
        }

        $context = match ($type) {
            BillingTemplate::TYPE_INVOICE => ['invoice' => $this->sampleFactory->createInvoice()],
            BillingTemplate::TYPE_QUOTE => ['quote' => $this->sampleFactory->createQuote()],
        };

        try {
            $html = $this->resolver->renderPreview($this->twig, $type, BillingTemplate::VARIANT_PDF, $content, $context);
            $pdf = $this->pdfGenerator->generate($html);
        } catch (TwigError $error) {
            return new JsonResponse(['error' => $error->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (MpdfException $error) {
            return new JsonResponse(['error' => 'PDF generation failed: ' . $error->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (InvalidArgumentException $error) {
            return new JsonResponse(['error' => $error->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return new PdfResponse($pdf, sprintf('%s-preview.pdf', $type));
    }
}
