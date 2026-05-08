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
use SolidInvoice\CoreBundle\Feature\UpgradePromptProvider;
use SolidInvoice\SaasBundle\Feature\UpgradePromptRenderer;
use SolidInvoice\SaasBundle\Tests\SaasTestKernel;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Verifies the `UpgradePromptProvider` interface resolves to
 * `UpgradePromptRenderer` when SaasBundle is loaded. The CoreBundle no-op
 * binding (`NullUpgradePromptProvider`) is exercised by the gate tests'
 * self-hosted assertions.
 */
final class UpgradePromptProviderWiringTest extends KernelTestCase
{
    /**
     * @param array<string, mixed> $options
     */
    #[Override]
    protected static function createKernel(array $options = []): KernelInterface
    {
        return new SaasTestKernel('test', true);
    }

    public function testUpgradePromptProviderResolvesToUpgradePromptRenderer(): void
    {
        self::bootKernel();

        $container = self::getContainer();
        $id = 'test.' . UpgradePromptProvider::class;

        self::assertTrue($container->has($id));
        self::assertInstanceOf(UpgradePromptRenderer::class, $container->get($id));
    }
}
