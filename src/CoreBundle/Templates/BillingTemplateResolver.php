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

namespace SolidInvoice\CoreBundle\Templates;

use SolidInvoice\CoreBundle\Contracts\PaidSubscriptionGateInterface;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\SaasBundle\Feature\Feature;
use SolidInvoice\SettingsBundle\SystemConfig;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;
use SolidWorx\Toggler\ToggleInterface;

/**
 * Resolves which Twig template renders an invoice or quote for a given
 * channel (PDF, email body, browser view).
 *
 * The company-selected design template (the `design/template` setting, seeded
 * by the SaaS bundle) only takes effect when every gate passes at render
 * time; otherwise the built-in default template is used. Gating at render
 * time — the same pattern as `hide_powered_by` + `feature_enabled()` — means
 * a plan downgrade, an ended trial or a paused subscription automatically
 * falls back to the default without any state to clean up.
 *
 * @see \SolidInvoice\CoreBundle\Tests\Templates\BillingTemplateResolverTest
 */
final readonly class BillingTemplateResolver
{
    public const string TEMPLATE_SETTING_KEY = 'design/template';

    public function __construct(
        private BillingTemplateRegistry $registry,
        private SystemConfig $systemConfig,
        private FeatureGate $featureGate,
        private PaidSubscriptionGateInterface $subscriptionGate,
        private ToggleInterface $toggle,
    ) {
    }

    /**
     * The template to render, falling back to the built-in default.
     */
    public function resolve(Invoice | Quote $document, BillingTemplateChannel $channel): string
    {
        return $this->customTemplate($document, $channel)
            ?? self::defaultTemplate($this->documentType($document), $channel);
    }

    /**
     * The company's custom template for this document/channel, or null when
     * the default should be used (self-hosted, no selection, feature not on
     * the plan, subscription not active, or the variant does not exist).
     */
    public function customTemplate(Invoice | Quote $document, BillingTemplateChannel $channel): ?string
    {
        // Custom design templates are a hosted-only capability: self-hosted
        // installs always render the built-in default.
        if (! $this->toggle->isActive('saas_enabled')) {
            return null;
        }

        $company = $document->getCompany();
        $slug = $this->systemConfig->get(self::TEMPLATE_SETTING_KEY, $company);

        if (null === $slug || '' === $slug || BillingTemplateRegistry::DEFAULT_SLUG === $slug) {
            return null;
        }

        if (! $this->subscriptionGate->isActive($company)) {
            return null;
        }

        if (! $this->featureGate->isEnabled(Feature::CustomTemplates->value, $company)) {
            return null;
        }

        return $this->registry->templatePath($slug, $this->documentType($document), $channel);
    }

    public static function defaultTemplate(BillingDocumentType $documentType, BillingTemplateChannel $channel): string
    {
        return match ([$documentType, $channel]) {
            [BillingDocumentType::Invoice, BillingTemplateChannel::Pdf] => '@SolidInvoiceInvoice/Pdf/invoice.html.twig',
            [BillingDocumentType::Invoice, BillingTemplateChannel::Email] => '@SolidInvoiceInvoice/Email/invoice.html.twig',
            [BillingDocumentType::Invoice, BillingTemplateChannel::View] => '@SolidInvoiceInvoice/external_invoice_view.html.twig',
            [BillingDocumentType::Quote, BillingTemplateChannel::Pdf] => '@SolidInvoiceQuote/Pdf/quote.html.twig',
            [BillingDocumentType::Quote, BillingTemplateChannel::Email] => '@SolidInvoiceQuote/Email/quote.html.twig',
            [BillingDocumentType::Quote, BillingTemplateChannel::View] => '@SolidInvoiceQuote/quote_template.html.twig',
        };
    }

    private function documentType(Invoice | Quote $document): BillingDocumentType
    {
        return $document instanceof Quote ? BillingDocumentType::Quote : BillingDocumentType::Invoice;
    }
}
