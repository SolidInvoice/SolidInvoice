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
use SolidInvoice\InstallBundle\Step\InstallationStepInterface;
use SolidInvoice\InstallBundle\Step\RunMigrationsStep;

/**
 * @covers \SolidInvoice\InstallBundle\Step\RunMigrationsStep
 */
final class RunMigrationsStepTest extends TestCase
{
    public function testPriority(): void
    {
        self::assertSame(10, RunMigrationsStep::priority());
    }

    public function testGetLabel(): void
    {
        self::assertSame('Creating database schema', RunMigrationsStep::getLabel());
    }

    public function testImplementsInstallationStepInterface(): void
    {
        self::assertTrue(is_a(RunMigrationsStep::class, InstallationStepInterface::class, true));
    }
}
