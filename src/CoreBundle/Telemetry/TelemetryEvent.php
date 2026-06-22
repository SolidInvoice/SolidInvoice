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

namespace SolidInvoice\CoreBundle\Telemetry;

/**
 * The fixed vocabulary of named lifecycle events emitted to SolidWorx Insights.
 *
 * The backing value is the wire format sent in the telemetry payload and must
 * remain stable.
 */
enum TelemetryEvent: string
{
    case InstallCompleted = 'install_completed';
    case Update = 'update';
    case CompanyCreated = 'company_created';
    case ClientCreated = 'client_created';
    case UserCreated = 'user_created';
    case InvoiceCreated = 'invoice_created';
    case RecurringInvoiceCreated = 'recurring_invoice_created';
    case QuoteCreated = 'quote_created';
    case PaymentReceived = 'payment_received';

    // SaaS (hosted) upgrade-funnel events. Emitted only by SaasBundle, which is
    // registered only when SOLIDINVOICE_PLATFORM=saas, so these never fire on
    // self-hosted installations. See docs/superpowers/specs/2026-06-22-saas-conversion-telemetry-design.md
    case SaasPricingPageViewed = 'saas_pricing_page_viewed';
    case SaasPlanSelected = 'saas_plan_selected';
    case SaasCheckoutStarted = 'saas_checkout_started';
    case SaasCheckoutFailed = 'saas_checkout_failed';
    case SaasSubscriptionActivated = 'saas_subscription_activated';
}
