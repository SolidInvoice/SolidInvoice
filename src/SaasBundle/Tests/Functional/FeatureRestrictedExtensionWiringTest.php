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

namespace SolidInvoice\SaasBundle\Tests\Functional;

use Override;
use SolidInvoice\SaasBundle\Feature\RequiredPlanLabelProvider;
use SolidInvoice\SaasBundle\Feature\UpgradePromptRenderer;
use SolidInvoice\SaasBundle\Form\Extension\FeatureRestrictedExtension;
use SolidInvoice\SaasBundle\Tests\SaasTestKernel;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Verifies SaaS-side wiring for the form-level feature gating mechanism:
 *  - the `RequiredPlanLabelProvider` interface resolves to UpgradePromptRenderer
 *  - the SaaS-only `FeatureRestrictedExtension` is registered as a service
 *
 * The non-SaaS counterpart (CoreBundle no-op extension) is exercised by the
 * existing form-test infrastructure; both sides must be present so the
 * `feature_gated` form option is accepted in every deployment.
 */
final class FeatureRestrictedExtensionWiringTest extends KernelTestCase
{
    #[Override]
    protected static function getKernelClass(): string
    {
        return SaasTestKernel::class;
    }

    public function testRequiredPlanLabelProviderResolvesToUpgradePromptRenderer(): void
    {
        self::bootKernel();

        $container = self::getContainer();
        $id = 'test.' . RequiredPlanLabelProvider::class;

        self::assertTrue($container->has($id));
        self::assertInstanceOf(UpgradePromptRenderer::class, $container->get($id));
    }

    public function testFeatureRestrictedExtensionIsRegistered(): void
    {
        self::bootKernel();

        $container = self::getContainer();
        $id = 'test.' . FeatureRestrictedExtension::class;

        self::assertTrue($container->has($id));
        self::assertInstanceOf(FeatureRestrictedExtension::class, $container->get($id));
    }
}
