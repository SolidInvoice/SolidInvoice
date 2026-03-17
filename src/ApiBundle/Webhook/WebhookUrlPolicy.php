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

use SolidWorx\Toggler\ToggleInterface;
use Symfony\Component\HttpFoundation\IpUtils;
use function filter_var;
use function in_array;
use function parse_url;
use function strtolower;

final class WebhookUrlPolicy
{
    /**
     * Cloud metadata endpoints that must always be blocked in hosted (SaaS) mode
     * to prevent SSRF attacks against instance metadata services.
     */
    private const array BLOCKED_METADATA_HOSTS = [
        'metadata.google.internal',    // GCP instance metadata
        '169.254.169.254',             // AWS / GCP / Azure shared metadata IP
        'metadata.azure.internal',     // Azure instance metadata
        'fd00:ec2::254',               // AWS IPv6 metadata
    ];

    public function __construct(
        private readonly ToggleInterface $toggler,
    ) {
    }

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

        // In SaaS/hosted mode, block private IPs and cloud metadata endpoints
        // to prevent SSRF against internal infrastructure.
        // Self-hosted users may legitimately target their own internal services.
        if ($this->toggler->isActive('saas_enabled')) {
            if (in_array(strtolower($host), self::BLOCKED_METADATA_HOSTS, true)) {
                return false;
            }

            if (filter_var($host, FILTER_VALIDATE_IP) !== false && IpUtils::isPrivateIp($host)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns true when the target host is a private/reserved IP range.
     * Useful for logging purposes in self-hosted mode.
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
