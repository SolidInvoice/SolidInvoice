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

namespace SolidInvoice\PaymentBundle\Tests\Gateway;

use PHPUnit\Framework\TestCase;
use SolidInvoice\PaymentBundle\Gateway\GatewayCategory;
use SolidInvoice\PaymentBundle\Gateway\GatewayInfo;
use SolidInvoice\PaymentBundle\Gateway\GatewayMetadataProvider;

final class GatewayMetadataProviderTest extends TestCase
{
    private GatewayMetadataProvider $provider;

    protected function setUp(): void
    {
        $this->provider = new GatewayMetadataProvider();
    }

    public function testRecommendedOnlineGatewayHasRichMetadata(): void
    {
        $info = $this->provider->get('stripe_checkout');

        self::assertSame('stripe_checkout', $info->name);
        self::assertSame('tabler:brand-stripe', $info->icon);
        self::assertSame(GatewayCategory::Online, $info->category);
        self::assertTrue($info->recommended);
        self::assertFalse($info->legacy);
        self::assertFalse($info->isAdvanced());
        self::assertTrue($info->hasGuidance());
        self::assertNotNull($info->overview);
        self::assertNotNull($info->comparison);
        self::assertSame('https://dashboard.stripe.com/apikeys', $info->setupUrl);
    }

    public function testSecondaryVariantIsMarkedAdvanced(): void
    {
        $info = $this->provider->get('stripe_js');

        self::assertFalse($info->recommended);
        self::assertTrue($info->isAdvanced());
        self::assertSame(GatewayCategory::Online, $info->category);
    }

    public function testOfflineGatewayHasNoSetupUrlButHasOverview(): void
    {
        $info = $this->provider->get('cash');

        self::assertSame(GatewayCategory::Offline, $info->category);
        self::assertNull($info->setupUrl);
        self::assertNull($info->comparison);
        self::assertNotNull($info->overview);
        self::assertTrue($info->hasGuidance());
        self::assertFalse($info->isAdvanced());
    }

    public function testLegacyGatewayIsFlaggedAndDeEmphasised(): void
    {
        $info = $this->provider->get('payex');

        self::assertTrue($info->legacy);
        self::assertFalse($info->recommended);
        self::assertFalse($info->isAdvanced());
    }

    public function testUnknownGatewayFallsBackToGenericMetadata(): void
    {
        $info = $this->provider->get('my-custom-wallet');

        self::assertInstanceOf(GatewayInfo::class, $info);
        self::assertSame('my-custom-wallet', $info->name);
        self::assertSame('My Custom Wallet', $info->displayName);
        self::assertSame('tabler:credit-card', $info->icon);
        self::assertSame(GatewayCategory::Custom, $info->category);
        self::assertFalse($info->hasGuidance());
    }

    public function testFindReturnsNullForUnknownGateway(): void
    {
        self::assertNull($this->provider->find('my-custom-wallet'));
        self::assertInstanceOf(GatewayInfo::class, $this->provider->find('stripe_checkout'));
    }

    public function testIconIsResolvedForKnownAndUnknownGateways(): void
    {
        self::assertSame('tabler:brand-paypal', $this->provider->icon('paypal_express_checkout'));
        self::assertSame('tabler:wallet', $this->provider->icon('offline'));
        self::assertSame('tabler:credit-card', $this->provider->icon('something-else'));
    }
}
