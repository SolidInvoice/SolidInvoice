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
use SolidInvoice\CoreBundle\ConfigWriter;
use SolidInvoice\InstallBundle\DTO\Installation;
use SolidInvoice\InstallBundle\Step\GenerateBuildIdStep;
use Symfony\Bundle\FrameworkBundle\Secrets\DotenvVault;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Uid\Uuid;
use function sys_get_temp_dir;
use function uniqid;

/**
 * @covers \SolidInvoice\InstallBundle\Step\GenerateBuildIdStep
 */
final class GenerateBuildIdStepTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/solid_invoice_build_id_step_test_' . uniqid(more_entropy: true);
        mkdir($this->tempDir, 0777, true);
    }

    protected function tearDown(): void
    {
        $fs = new Filesystem();
        $fs->exists($this->tempDir) && $fs->remove($this->tempDir);
    }

    public function testPriority(): void
    {
        self::assertSame(25, GenerateBuildIdStep::priority());
    }

    public function testGetLabel(): void
    {
        self::assertSame('Generating build id', GenerateBuildIdStep::getLabel());
    }

    public function testExecuteGeneratesAndSealsBuildId(): void
    {
        $dotenvFile = $this->tempDir . '/.env.local';
        $vault = new DotenvVault($dotenvFile);
        $configWriter = new ConfigWriter($vault, $this->tempDir);

        $step = new GenerateBuildIdStep($configWriter);
        iterator_to_array($step->execute(new Installation()));

        self::assertFileExists($dotenvFile);
        $contents = file_get_contents($dotenvFile);
        self::assertStringContainsString('SOLIDINVOICE_BUILD_ID=', $contents);

        // Extract the stored UUID and validate it
        preg_match("/SOLIDINVOICE_BUILD_ID='([^']+)'/", $contents, $matches);
        self::assertNotEmpty($matches[1] ?? '');
        self::assertTrue(Uuid::isValid($matches[1]));
    }

    public function testExecuteWithCallback(): void
    {
        $vault = new DotenvVault($this->tempDir . '/.env.local');
        $configWriter = new ConfigWriter($vault, $this->tempDir);

        $step = new GenerateBuildIdStep($configWriter);

        $callbackMessages = [];
        $callback = static function (string $message) use (&$callbackMessages): \Generator {
            $callbackMessages[] = $message;
            yield;
        };

        iterator_to_array($step->execute(new Installation(), $callback));

        self::assertCount(1, $callbackMessages);
        self::assertSame('Build ID generated', $callbackMessages[0]);
    }

    public function testExecuteWithoutCallback(): void
    {
        $vault = new DotenvVault($this->tempDir . '/.env.local');
        $configWriter = new ConfigWriter($vault, $this->tempDir);

        $step = new GenerateBuildIdStep($configWriter);
        $result = iterator_to_array($step->execute(new Installation()));

        self::assertSame([], $result);
    }
}
