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

namespace SolidInvoice\ApiBundle\Webhook;

use function filter_var;
use function in_array;
use function parse_url;

final class WebhookUrlPolicy
{
    private const PRIVATE_RANGES = [
        // IPv4 private/reserved ranges
        '10.',
        '172.16.', '172.17.', '172.18.', '172.19.',
        '172.20.', '172.21.', '172.22.', '172.23.',
        '172.24.', '172.25.', '172.26.', '172.27.',
        '172.28.', '172.29.', '172.30.', '172.31.',
        '192.168.',
        '127.',
        '169.254.',
        '0.',
    ];

    private const BLOCKED_HOSTS = [
        'localhost',
        'metadata.google.internal',
    ];

    public function isAllowed(string $url): bool
    {
        $parsed = parse_url($url);

        if ($parsed === false) {
            return false;
        }

        $scheme = $parsed['scheme'] ?? '';
        if (! in_array($scheme, ['http', 'https'], true)) {
            return false;
        }

        $host = $parsed['host'] ?? '';
        if ($host === '') {
            return false;
        }

        if (in_array($host, self::BLOCKED_HOSTS, true)) {
            return false;
        }

        // Reject IP addresses in private/reserved ranges
        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            foreach (self::PRIVATE_RANGES as $prefix) {
                if (str_starts_with($host, $prefix)) {
                    return false;
                }
            }

            // Block IPv6 loopback
            if ($host === '::1' || $host === '[::1]') {
                return false;
            }
        }

        return true;
    }
}
