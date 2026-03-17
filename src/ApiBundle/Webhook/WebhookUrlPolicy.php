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

use Symfony\Component\HttpFoundation\IpUtils;
use function filter_var;
use function in_array;
use function parse_url;

final class WebhookUrlPolicy
{
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

        return true;
    }

    /**
     * Returns true when the target host resolves to a private/reserved IP range.
     * SolidInvoice is self-hosted, so private-network targets are permitted by
     * default — callers can use this to log or warn without hard-blocking.
     */
    public function isPrivateHost(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST) ?? '';

        if ($host === '') {
            return false;
        }

        if (filter_var($host, FILTER_VALIDATE_IP) === false) {
            return false;
        }

        return IpUtils::isPrivateIp($host);
    }
}
