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

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return App::config([
    'framework' => [
        'rate_limiter' => [
            'limiters' => [
                'api_global' => [
                    'policy' => 'sliding_window',
                    'limit' => 300,
                    'interval' => '1 minute',
                ],
                // "Email me a test copy" mails an address nobody has verified yet, with content the
                // account controls. Cap it per account for its unverified life, and per IP so new
                // accounts don't multiply that.
                // POST /api/invoices/{id}/reminder, keyed by invoice.
                'invoice_manual_reminder' => [
                    'policy' => 'fixed_window',
                    'limit' => 1,
                    'interval' => '1 hour',
                ],
                'invoice_test_copy' => [
                    'policy' => 'fixed_window',
                    'limit' => 3,
                    'interval' => '30 days',
                ],
                'invoice_test_copy_ip' => [
                    'policy' => 'fixed_window',
                    'limit' => 10,
                    'interval' => '1 day',
                ],
                'mcp_oauth_register' => [
                    'policy' => 'fixed_window',
                    'limit' => 60,
                    'interval' => '1 hour',
                ],
                // SaaS: invoice and quote emails a company may send per day in its first week.
                // Past it they are held for review, not dropped (HoldNewAccountClientEmailListener).
                'new_account_client_email' => [
                    'policy' => 'sliding_window',
                    'limit' => 3,
                    'interval' => '1 day',
                ],
                'one_tap_nonce' => [
                    'policy' => 'sliding_window',
                    'limit' => 30,
                    'interval' => '1 minute',
                ],
                'one_tap_verify' => [
                    'policy' => 'sliding_window',
                    'limit' => 15,
                    'interval' => '1 minute',
                ],
            ],
        ],
    ],
]);
