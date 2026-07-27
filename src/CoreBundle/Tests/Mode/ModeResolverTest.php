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

namespace SolidInvoice\CoreBundle\Tests\Mode;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Mode\ApplicationMode;
use SolidInvoice\CoreBundle\Mode\Capability;
use SolidInvoice\CoreBundle\Mode\ModeResolver;

final class ModeResolverTest extends TestCase
{
    public function testDefaultsToSelfHostedAllowingEverything(): void
    {
        $resolver = new ModeResolver();

        self::assertSame(ApplicationMode::SelfHosted, $resolver->current());
        self::assertTrue($resolver->isSelfHosted());
        self::assertFalse($resolver->isDemo());
        foreach (Capability::cases() as $capability) {
            self::assertTrue($resolver->allows($capability), $capability->name);
        }
    }

    public function testDemoModeDeniesRestrictedCapabilities(): void
    {
        $resolver = new ModeResolver('demo', 'demo@example.com', 'secret', 'https://signup.example.com');

        self::assertTrue($resolver->isDemo());
        self::assertFalse($resolver->allows(Capability::UserRegistration));
        self::assertFalse($resolver->allows(Capability::RealEmailDelivery));
        self::assertFalse($resolver->allows(Capability::RealNotificationDelivery));
        self::assertFalse($resolver->allows(Capability::OnlinePaymentCapture));
        self::assertFalse($resolver->allows(Capability::CredentialChange));
        self::assertSame('demo@example.com', $resolver->demoUsername());
        self::assertSame('secret', $resolver->demoPassword());
        self::assertSame('https://signup.example.com', $resolver->demoSignupUrl());
    }

    public function testSaasModeAllowsEverythingAtThisLayer(): void
    {
        $resolver = new ModeResolver('saas');

        self::assertTrue($resolver->isSaas());
        foreach (Capability::cases() as $capability) {
            self::assertTrue($resolver->allows($capability), $capability->name);
        }
        self::assertNull($resolver->demoUsername());
    }

    public function testUnknownModeThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new ModeResolver('bogus'))->current();
    }

    /**
     * Guard against the fail-open nature of ModeResolver::DENIED: a newly added Capability
     * case is allowed in demo mode by default until it is explicitly added to the DENIED map.
     * This test documents the expectation that, today, every capability is demo-denied. When a
     * new capability is intentionally allowed in demo, update this test deliberately.
     */
    public function testDemoModeDeniesEveryCurrentCapability(): void
    {
        $resolver = new ModeResolver('demo', 'demo@example.com', 'secret');

        foreach (Capability::cases() as $capability) {
            self::assertFalse($resolver->allows($capability), $capability->name);
        }
    }
}
