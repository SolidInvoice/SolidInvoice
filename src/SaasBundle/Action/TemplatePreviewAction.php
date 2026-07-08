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

namespace SolidInvoice\SaasBundle\Action;

use SolidInvoice\CoreBundle\Templates\BillingDocumentType;
use SolidInvoice\CoreBundle\Templates\BillingTemplateChannel;
use SolidInvoice\CoreBundle\Templates\BillingTemplateRegistry;
use SolidInvoice\CoreBundle\Templates\BillingTemplateResolver;
use SolidInvoice\SaasBundle\Templates\PreviewInvoiceFactory;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Twig\Environment;
use function sprintf;
use function str_replace;

/**
 * Renders a design template with in-memory sample data so the settings page
 * can show live previews. Intentionally not feature-gated: users on lower
 * plans can browse the gallery before upgrading.
 *
 * @see \SolidInvoice\SaasBundle\Tests\Action\TemplatePreviewActionTest
 */
final readonly class TemplatePreviewAction
{
    /**
     * The default design's PDF document is laid out for mPDF's fixed page, so
     * in a browser it stretches edge-to-edge. Constrain it to a page-like
     * sheet matching the framing the other previews get from their wrapper.
     */
    private const string DEFAULT_PREVIEW_STYLES = <<<'HTML'
        <style>
            html { background: #f1f5f9; }
            body { max-width: 800px; margin: 1.5rem auto; padding: 2.5rem; background: #ffffff; border-radius: 4px; box-shadow: 0 1px 3px rgba(15, 23, 42, 0.1); }
        </style></head>
        HTML;

    public function __construct(
        private BillingTemplateRegistry $registry,
        private PreviewInvoiceFactory $invoiceFactory,
        private Environment $twig,
    ) {
    }

    public function __invoke(string $slug): Response
    {
        $invoice = $this->invoiceFactory->create();

        if (BillingTemplateRegistry::DEFAULT_SLUG === $slug) {
            // The built-in default has no browser fragment; its PDF template is
            // a self-contained HTML document that previews faithfully.
            $html = $this->twig->render(
                BillingTemplateResolver::defaultTemplate(BillingDocumentType::Invoice, BillingTemplateChannel::Pdf),
                ['invoice' => $invoice],
            );

            return new Response(str_replace('</head>', self::DEFAULT_PREVIEW_STYLES, $html));
        }

        $template = $this->registry->templatePath($slug, BillingDocumentType::Invoice, BillingTemplateChannel::View);

        if (null === $template) {
            throw new NotFoundHttpException(sprintf('Unknown template "%s"', $slug));
        }

        return new Response($this->twig->render('@SolidInvoiceSaas/Settings/template_preview.html.twig', [
            'invoice' => $invoice,
            'documentTemplate' => $template,
        ]));
    }
}
