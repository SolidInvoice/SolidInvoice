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
}
