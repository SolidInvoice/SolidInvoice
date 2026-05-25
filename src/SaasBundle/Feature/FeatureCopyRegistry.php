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
 * Strings are stored in English and translated downstream by `|trans` in
 * the template — translators override via the standard message catalogue.
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
                headline: 'Client limit reached',
                description: 'Manage more clients without juggling separate accounts. Upgrade to keep adding new business as you grow.',
                bullets: [
                    'Higher (or unlimited) client cap',
                    'Same workflows, no migration',
                    'Upgrade or downgrade anytime',
                ],
            ),
            Feature::InvoicesPerMonth => new FeatureCopy(
                icon: 'tabler:file-invoice',
                headline: 'Monthly invoice limit reached',
                description: 'Keep billing without interruption. Higher plans lift the monthly cap so your cashflow never has to wait.',
                bullets: [
                    'Higher (or unlimited) monthly invoices',
                    'No throttling at month-end',
                    'Recurring invoices keep firing',
                ],
            ),
            Feature::TeamSeats => new FeatureCopy(
                icon: 'tabler:users-group',
                headline: 'Invite your team',
                description: 'Bring teammates into SolidInvoice so quoting, invoicing and follow-up stop being a one-person job.',
                bullets: [
                    'Multiple seats with role-based access',
                    'Shared client and invoice history',
                    'Per-user activity audit trail',
                ],
            ),
            Feature::Quotes => new FeatureCopy(
                icon: 'tabler:file-text',
                headline: 'Send professional quotes',
                description: "Win more work with polished, branded quotes that convert into invoices the moment they're accepted.",
                bullets: [
                    'Custom-branded PDF quotes',
                    'One-click convert to invoice',
                    'Track sent, viewed and accepted',
                ],
            ),
            Feature::OnlinePayments => new FeatureCopy(
                icon: 'tabler:credit-card',
                headline: 'Get paid online',
                description: 'Add a Pay Now button to every invoice using your own Stripe or PayPal keys — you keep the merchant relationship.',
                bullets: [
                    'Stripe, PayPal and other Payum gateways',
                    'Bring your own payment keys',
                    'Auto-marks invoices paid on settlement',
                ],
            ),
            Feature::RecurringInvoices => new FeatureCopy(
                icon: 'tabler:rotate-clockwise',
                headline: 'Recurring invoices on autopilot',
                description: 'Set it once and get paid every month. Recurring billing handles retainers, subscriptions and any repeating work.',
                bullets: [
                    'Daily, weekly, monthly or custom schedules',
                    'Auto-send on the day they generate',
                    'Pause, resume or end anytime',
                ],
            ),
            Feature::AutomatedReminders => new FeatureCopy(
                icon: 'tabler:bell-ringing',
                headline: 'Automated payment reminders',
                description: 'Stop chasing invoices manually. SolidInvoice nudges clients on the schedule you choose until they pay.',
                bullets: [
                    'Configurable reminder cadence',
                    'Custom email copy per stage',
                    'Stops automatically on payment',
                ],
            ),
            Feature::MultiCurrency => new FeatureCopy(
                icon: 'tabler:currency-dollar',
                headline: 'Bill clients in their currency',
                description: 'Quote and invoice each client in the currency they expect, with money values handled correctly end-to-end.',
                bullets: [
                    'Per-client currency override',
                    'Accurate, no-floating-point money math',
                    'Reports normalised to your base currency',
                ],
            ),
            Feature::CustomBranding => new FeatureCopy(
                icon: 'tabler:palette',
                headline: 'Brand it as your own',
                description: 'Replace SolidInvoice branding on the client portal, PDFs and emails so every touchpoint looks like you.',
                bullets: [
                    'Custom logo on quotes, invoices and emails',
                    'Hide "Powered by SolidInvoice"',
                    'Branded client-portal experience',
                ],
            ),
            Feature::RestApiAccess => new FeatureCopy(
                icon: 'tabler:plug-connected',
                headline: 'Programmatic access via REST API',
                description: 'Wire SolidInvoice into your stack — sync clients, push invoices, automate billing from anywhere.',
                bullets: [
                    'Full REST API (JSON-LD, HAL, JSON, XML)',
                    'API tokens with revocation',
                    'Integrations: Zapier, n8n, your own code',
                ],
            ),
            Feature::McpAccess => new FeatureCopy(
                icon: 'tabler:robot',
                headline: 'Let AI agents work for you',
                description: 'Give Claude, Cursor or any MCP-aware agent secure access to your SolidInvoice data so they can quote, invoice and report on your behalf.',
                bullets: [
                    'Native Model Context Protocol server',
                    'OAuth-style scoped agent access',
                    'Works with Claude, Cursor, Cline and more',
                ],
            ),
            Feature::CustomDomain => new FeatureCopy(
                icon: 'tabler:world',
                headline: 'Use your own domain',
                description: 'Serve the client portal from billing.yourcompany.com instead of a SolidInvoice subdomain — the kind of polish clients notice.',
                bullets: [
                    'CNAME any domain you own',
                    'Automatic TLS certificates',
                    'Branded portal and email links',
                ],
            ),
            Feature::CustomFields => new FeatureCopy(
                icon: 'tabler:forms',
                headline: 'Capture the data that matters to you',
                description: 'Add your own fields to clients, contacts, invoices and quotes — track project codes, PO numbers or anything unique to how you bill.',
                bullets: [
                    'Custom fields on clients, contacts, invoices and quotes',
                    'Show internal-only or client-visible on PDFs',
                    'Auto-copied on recurring invoices and quote→invoice conversion',
                ],
            ),
        };
    }
}
