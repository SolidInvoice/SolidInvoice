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

use SolidWorx\Platform\PlatformBundle\Feature\FeatureType;

/**
 * Canonical catalogue of every gated feature in SolidInvoice's hosted SaaS plans.
 *
 * The string values intentionally match the keys registered in
 * `solidworx_platform.saas.features` (platform.yaml) and stored in the
 * `saas_plan_feature.feature_key` column. Keep the two in sync — the
 * `FeatureCatalogTest` functional test asserts there is no drift.
 */
enum Feature: string
{
    case TotalClients = 'total_clients';
    case InvoicesPerMonth = 'invoices_per_month';
    case TeamSeats = 'team_seats';
    case Quotes = 'quotes';
    case OnlinePayments = 'online_payments';
    case RecurringInvoices = 'recurring_invoices';
    case AutomatedReminders = 'automated_reminders';
    case MultiCurrency = 'multi_currency';
    case CustomBranding = 'custom_branding';
    case RestApiAccess = 'rest_api_access';
    case McpAccess = 'mcp_access';
    case CustomDomain = 'custom_domain';
    case CustomFields = 'custom_fields';

    public function getType(): FeatureType
    {
        return match ($this) {
            self::TotalClients,
            self::InvoicesPerMonth,
            self::TeamSeats => FeatureType::INTEGER,
            self::Quotes,
            self::OnlinePayments,
            self::RecurringInvoices,
            self::AutomatedReminders,
            self::MultiCurrency,
            self::CustomBranding,
            self::RestApiAccess,
            self::McpAccess,
            self::CustomDomain,
            self::CustomFields => FeatureType::BOOLEAN,
        };
    }
}
