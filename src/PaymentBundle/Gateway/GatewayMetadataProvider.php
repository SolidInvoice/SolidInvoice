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

namespace SolidInvoice\PaymentBundle\Gateway;

use function preg_replace;
use function str_replace;
use function ucwords;

/**
 * Central registry of presentation metadata for the payment gateways.
 *
 * Both the marketplace and the gateway configuration screen read their labels,
 * icons, descriptions and setup instructions from here, so the copy lives in one
 * place instead of being duplicated across the Twig live components.
 *
 * @see \SolidInvoice\PaymentBundle\Tests\Gateway\GatewayMetadataProviderTest
 */
final class GatewayMetadataProvider
{
    /**
     * @var array<string, GatewayInfo>|null
     */
    private ?array $metadata = null;

    /**
     * Look up metadata for a gateway, falling back to a sensible generic
     * description when the gateway is unknown (e.g. a custom-named method).
     */
    public function get(string $name): GatewayInfo
    {
        return $this->find($name) ?? $this->fallback($name);
    }

    /**
     * Look up metadata for a known gateway, or null when it is not one of the
     * built-in gateways.
     */
    public function find(string $name): ?GatewayInfo
    {
        return $this->metadata()[$name] ?? null;
    }

    public function icon(string $name): string
    {
        return $this->get($name)->icon;
    }

    /**
     * @return array<string, GatewayInfo>
     */
    private function metadata(): array
    {
        if ($this->metadata !== null) {
            return $this->metadata;
        }

        $key = static fn (string $gateway, string $attribute): string => "payment.gateway.{$gateway}.{$attribute}";

        $gateways = [
            new GatewayInfo(
                name: 'stripe_checkout',
                displayName: $key('stripe_checkout', 'display_name'),
                tagline: $key('stripe_checkout', 'tagline'),
                icon: 'tabler:brand-stripe',
                category: GatewayCategory::Online,
                recommended: true,
                overview: $key('stripe_checkout', 'overview'),
                comparison: $key('stripe', 'comparison'),
                setupUrl: 'https://dashboard.stripe.com/apikeys',
                setupUrlLabel: $key('stripe', 'setup_label'),
            ),
            new GatewayInfo(
                name: 'stripe_js',
                displayName: $key('stripe_js', 'display_name'),
                tagline: $key('stripe_js', 'tagline'),
                icon: 'tabler:brand-stripe',
                category: GatewayCategory::Online,
                overview: $key('stripe_js', 'overview'),
                comparison: $key('stripe', 'comparison'),
                setupUrl: 'https://dashboard.stripe.com/apikeys',
                setupUrlLabel: $key('stripe', 'setup_label'),
            ),
            new GatewayInfo(
                name: 'paypal_express_checkout',
                displayName: $key('paypal_express_checkout', 'display_name'),
                tagline: $key('paypal_express_checkout', 'tagline'),
                icon: 'tabler:brand-paypal',
                category: GatewayCategory::Online,
                recommended: true,
                overview: $key('paypal_express_checkout', 'overview'),
                comparison: $key('paypal', 'comparison'),
                setupUrl: 'https://developer.paypal.com/api/nvp-soap/apiCredentials/',
                setupUrlLabel: $key('paypal', 'setup_label'),
            ),
            new GatewayInfo(
                name: 'paypal_pro_checkout',
                displayName: $key('paypal_pro_checkout', 'display_name'),
                tagline: $key('paypal_pro_checkout', 'tagline'),
                icon: 'tabler:brand-paypal',
                category: GatewayCategory::Online,
                overview: $key('paypal_pro_checkout', 'overview'),
                comparison: $key('paypal', 'comparison'),
                setupUrl: 'https://developer.paypal.com/api/nvp-soap/apiCredentials/',
                setupUrlLabel: $key('paypal', 'setup_label'),
            ),
            new GatewayInfo(
                name: 'offline',
                displayName: $key('offline', 'display_name'),
                tagline: $key('offline', 'tagline'),
                icon: 'tabler:wallet',
                category: GatewayCategory::Offline,
                recommended: true,
                overview: $key('offline', 'overview'),
            ),
            new GatewayInfo(
                name: 'cash',
                displayName: $key('cash', 'display_name'),
                tagline: $key('cash', 'tagline'),
                icon: 'tabler:cash',
                category: GatewayCategory::Offline,
                overview: $key('cash', 'overview'),
            ),
            new GatewayInfo(
                name: 'bank_transfer',
                displayName: $key('bank_transfer', 'display_name'),
                tagline: $key('bank_transfer', 'tagline'),
                icon: 'tabler:building-bank',
                category: GatewayCategory::Offline,
                overview: $key('bank_transfer', 'overview'),
            ),
            new GatewayInfo(
                name: 'custom',
                displayName: $key('custom', 'display_name'),
                tagline: $key('custom', 'tagline'),
                icon: 'tabler:settings',
                category: GatewayCategory::Custom,
                overview: $key('custom', 'overview'),
            ),
        ];

        // Legacy processors: kept available but de-emphasised in a "More gateways" group.
        foreach (['klarna_invoice', 'klarna_checkout', 'be2bill_offsite', 'be2bill_direct', 'authorize_net_aim', 'payex'] as $legacy) {
            $gateways[] = new GatewayInfo(
                name: $legacy,
                displayName: $key($legacy, 'display_name'),
                tagline: $key($legacy, 'tagline'),
                icon: 'tabler:credit-card',
                category: GatewayCategory::Online,
                legacy: true,
            );
        }

        $this->metadata = [];
        foreach ($gateways as $gateway) {
            $this->metadata[$gateway->name] = $gateway;
        }

        return $this->metadata;
    }

    private function fallback(string $name): GatewayInfo
    {
        return new GatewayInfo(
            name: $name,
            displayName: $this->humanize($name),
            tagline: 'payment.gateway._fallback.tagline',
            icon: 'tabler:credit-card',
            category: GatewayCategory::Custom,
        );
    }

    private function humanize(string $name): string
    {
        // Sanitize the gateway name to prevent XSS - only keep alphanumeric, underscores and hyphens.
        $sanitized = preg_replace('/[^a-z0-9_-]/i', '', $name);

        return ucwords(str_replace(['_', '-'], ' ', (string) $sanitized));
    }
}
