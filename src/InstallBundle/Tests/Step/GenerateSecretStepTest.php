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

namespace SolidInvoice\InstallBundle\Tests\Step;

use PHPUnit\Framework\TestCase;
use SolidInvoice\InstallBundle\Step\GenerateSecretStep;
use SolidInvoice\InstallBundle\Step\InstallationStepInterface;

/**
 * @covers \SolidInvoice\InstallBundle\Step\GenerateSecretStep
 */
final class GenerateSecretStepTest extends TestCase
{
    public function testPriority(): void
    {
        self::assertSame(30, GenerateSecretStep::priority());
    }

    public function testGetLabel(): void
    {
        self::assertSame('Generating secret', GenerateSecretStep::getLabel());
    }

    public function testImplementsInstallationStepInterface(): void
    {
        self::assertTrue(is_a(GenerateSecretStep::class, InstallationStepInterface::class, true));
    }
}
