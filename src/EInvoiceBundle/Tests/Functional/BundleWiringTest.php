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

namespace SolidInvoice\EInvoiceBundle\Tests\Functional;

use SolidInvoice\EInvoiceBundle\Profile\ProfileRegistry;
use SolidInvoice\EInvoiceBundle\SolidInvoiceEInvoiceBundle;
use SolidInvoice\EInvoiceBundle\Validation\ValidatorRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Smoke test: verifies the bundle boots and its two self-contained registries
 * resolve to empty, RewindableGenerator-backed lists.
 *
 * The test container only exposes private services reachable from public ones.
 * config/services_test.php provides public aliases (prefixed with "test.") so
 * these wiring contracts can be introspected in functional tests.
 */
final class BundleWiringTest extends KernelTestCase
{
    public function testBundleIsRegistered(): void
    {
        self::bootKernel();

        $bundleClasses = array_map(
            static fn (object $bundle): string => $bundle::class,
            self::$kernel->getBundles(),
        );

        self::assertContains(SolidInvoiceEInvoiceBundle::class, $bundleClasses);
    }

    public function testProfileRegistryAliasResolvesAndIsRepeatable(): void
    {
        self::bootKernel();

        $container = self::getContainer();

        self::assertTrue($container->has('test.' . ProfileRegistry::class));

        $registry = $container->get('test.' . ProfileRegistry::class);

        self::assertInstanceOf(ProfileRegistry::class, $registry);
        self::assertSame([], $registry->all());
        self::assertSame([], $registry->all());
    }

    public function testValidatorRegistryAliasResolvesAndIsRepeatable(): void
    {
        self::bootKernel();

        $container = self::getContainer();

        self::assertTrue($container->has('test.' . ValidatorRegistry::class));

        $registry = $container->get('test.' . ValidatorRegistry::class);

        self::assertInstanceOf(ValidatorRegistry::class, $registry);
        self::assertSame([], $registry->all());
        self::assertSame([], $registry->all());
    }
}
