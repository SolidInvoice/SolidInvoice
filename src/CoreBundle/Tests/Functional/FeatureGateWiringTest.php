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

namespace SolidInvoice\CoreBundle\Tests\Functional;

use SolidInvoice\CoreBundle\Company\CompanySubscriberResolver;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;
use SolidWorx\Platform\PlatformBundle\Feature\NoopFeatureGate;
use SolidWorx\Platform\PlatformBundle\Feature\SubscriberResolver;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Smoke test: verifies that the FeatureGate and SubscriberResolver aliases
 * resolve to the correct concrete implementations for the current environment.
 *
 * In non-SaaS (self-hosted) mode, FeatureGate → NoopFeatureGate.
 * In all modes, SubscriberResolver → CompanySubscriberResolver (CoreBundle compiler pass).
 *
 * The test container only exposes private services reachable from public ones.
 * config/services_test.php provides public aliases (prefixed with "test.") so
 * these wiring contracts can be introspected in functional tests.
 */
final class FeatureGateWiringTest extends KernelTestCase
{
    public function testFeatureGateAliasResolves(): void
    {
        self::bootKernel();

        $container = self::getContainer();

        self::assertTrue($container->has('test.' . FeatureGate::class));
        self::assertInstanceOf(NoopFeatureGate::class, $container->get('test.' . FeatureGate::class));
    }

    public function testSubscriberResolverAliasResolvesToCompanyResolver(): void
    {
        self::bootKernel();

        $container = self::getContainer();

        self::assertTrue($container->has('test.' . SubscriberResolver::class));
        self::assertInstanceOf(CompanySubscriberResolver::class, $container->get('test.' . SubscriberResolver::class));
    }
}
