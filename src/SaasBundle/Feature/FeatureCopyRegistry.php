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

namespace SolidInvoice\SaasBundle\Feature;

/**
 * Single source of truth for the marketing copy shown on gated-feature
 * surfaces (full-page gate, inline banner). Keyed by the `Feature` enum so
 * the catalogue cannot drift from the gates that actually fire.
 *
 * Twig templates consume this via the `feature_copy()` Twig function. When
 * a Twig caller passes an explicit `headline`, `description`, `icon`, or
 * `bullets` parameter, it overrides the registry value for that one render.
 *
 * Text fields hold `messages` catalogue keys (`feature.<feature>.*`) and are
 * translated downstream by `|trans` in the template.
 * @see \SolidInvoice\SaasBundle\Tests\Feature\FeatureCopyRegistryTest
 */
final readonly class FeatureCopyRegistry
{
    public function get(string $featureKey): ?FeatureCopy
    {
        $feature = Feature::tryFrom($featureKey);

        if ($feature === null) {
            return null;
        }

        return match ($feature) {
            Feature::TotalClients => new FeatureCopy(
                icon: 'tabler:users-plus',
                headline: 'feature.total_clients.headline',
                description: 'feature.total_clients.description',
                bullets: [
                    'feature.total_clients.bullets.1',
                    'feature.total_clients.bullets.2',
                    'feature.total_clients.bullets.3',
                ],
            ),
            Feature::InvoicesPerMonth => new FeatureCopy(
                icon: 'tabler:file-invoice',
                headline: 'feature.invoices_per_month.headline',
                description: 'feature.invoices_per_month.description',
                bullets: [
                    'feature.invoices_per_month.bullets.1',
                    'feature.invoices_per_month.bullets.2',
                    'feature.invoices_per_month.bullets.3',
                ],
            ),
            Feature::TeamSeats => new FeatureCopy(
                icon: 'tabler:users-group',
                headline: 'feature.team_seats.headline',
                description: 'feature.team_seats.description',
                bullets: [
                    'feature.team_seats.bullets.1',
                    'feature.team_seats.bullets.2',
                    'feature.team_seats.bullets.3',
                ],
            ),
            Feature::Quotes => new FeatureCopy(
                icon: 'tabler:file-text',
                headline: 'feature.quotes.headline',
                description: 'feature.quotes.description',
                bullets: [
                    'feature.quotes.bullets.1',
                    'feature.quotes.bullets.2',
                    'feature.quotes.bullets.3',
                ],
            ),
            Feature::OnlinePayments => new FeatureCopy(
                icon: 'tabler:credit-card',
                headline: 'feature.online_payments.headline',
                description: 'feature.online_payments.description',
                bullets: [
                    'feature.online_payments.bullets.1',
                    'feature.online_payments.bullets.2',
                    'feature.online_payments.bullets.3',
                ],
            ),
            Feature::RecurringInvoices => new FeatureCopy(
                icon: 'tabler:rotate-clockwise',
                headline: 'feature.recurring_invoices.headline',
                description: 'feature.recurring_invoices.description',
                bullets: [
                    'feature.recurring_invoices.bullets.1',
                    'feature.recurring_invoices.bullets.2',
                    'feature.recurring_invoices.bullets.3',
                ],
            ),
            Feature::AutomatedReminders => new FeatureCopy(
                icon: 'tabler:bell-ringing',
                headline: 'feature.automated_reminders.headline',
                description: 'feature.automated_reminders.description',
                bullets: [
                    'feature.automated_reminders.bullets.1',
                    'feature.automated_reminders.bullets.2',
                    'feature.automated_reminders.bullets.3',
                ],
            ),
            Feature::MultiCurrency => new FeatureCopy(
                icon: 'tabler:currency-dollar',
                headline: 'feature.multi_currency.headline',
                description: 'feature.multi_currency.description',
                bullets: [
                    'feature.multi_currency.bullets.1',
                    'feature.multi_currency.bullets.2',
                    'feature.multi_currency.bullets.3',
                ],
            ),
            Feature::CustomBranding => new FeatureCopy(
                icon: 'tabler:palette',
                headline: 'feature.custom_branding.headline',
                description: 'feature.custom_branding.description',
                bullets: [
                    'feature.custom_branding.bullets.1',
                    'feature.custom_branding.bullets.2',
                    'feature.custom_branding.bullets.3',
                ],
            ),
            Feature::RestApiAccess => new FeatureCopy(
                icon: 'tabler:plug-connected',
                headline: 'feature.rest_api_access.headline',
                description: 'feature.rest_api_access.description',
                bullets: [
                    'feature.rest_api_access.bullets.1',
                    'feature.rest_api_access.bullets.2',
                    'feature.rest_api_access.bullets.3',
                ],
            ),
            Feature::McpAccess => new FeatureCopy(
                icon: 'tabler:robot',
                headline: 'feature.mcp_access.headline',
                description: 'feature.mcp_access.description',
                bullets: [
                    'feature.mcp_access.bullets.1',
                    'feature.mcp_access.bullets.2',
                    'feature.mcp_access.bullets.3',
                ],
            ),
            Feature::CustomDomain => new FeatureCopy(
                icon: 'tabler:world',
                headline: 'feature.custom_domain.headline',
                description: 'feature.custom_domain.description',
                bullets: [
                    'feature.custom_domain.bullets.1',
                    'feature.custom_domain.bullets.2',
                    'feature.custom_domain.bullets.3',
                ],
            ),
            Feature::CustomFields => new FeatureCopy(
                icon: 'tabler:forms',
                headline: 'feature.custom_fields.headline',
                description: 'feature.custom_fields.description',
                bullets: [
                    'feature.custom_fields.bullets.1',
                    'feature.custom_fields.bullets.2',
                    'feature.custom_fields.bullets.3',
                ],
            ),
            Feature::CustomTemplates => new FeatureCopy(
                icon: 'tabler:layout-2',
                headline: 'feature.custom_templates.headline',
                description: 'feature.custom_templates.description',
                bullets: [
                    'feature.custom_templates.bullets.1',
                    'feature.custom_templates.bullets.2',
                    'feature.custom_templates.bullets.3',
                ],
            ),
        };
    }
}
