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

namespace SolidInvoice\CoreBundle\Tests\Config\Loader;

use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Config\Loader\BuildIdLoader;
use SolidInvoice\CoreBundle\ConfigWriter;
use Symfony\Bundle\FrameworkBundle\Secrets\DotenvVault;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Uid\Uuid;
use function sys_get_temp_dir;
use function uniqid;

/**
 * @covers \SolidInvoice\CoreBundle\Config\Loader\BuildIdLoader
 */
final class BuildIdLoaderTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/solid_invoice_build_id_test_' . uniqid(more_entropy: true);
        mkdir($this->tempDir, 0777, true);
    }

    protected function tearDown(): void
    {
        $fs = new Filesystem();
        $fs->exists($this->tempDir) && $fs->remove($this->tempDir);
    }

    public function testReturnsEmptyArrayWhenBuildIdAlreadySet(): void
    {
        $vault = new DotenvVault($this->tempDir . '/.env.local');
        $configWriter = new ConfigWriter($vault, $this->tempDir);

        $loader = new BuildIdLoader($configWriter, 'existing-uuid-value');

        self::assertSame([], $loader->loadEnvVars());
    }

    public function testGeneratesAndReturnsBuildIdWhenNotSet(): void
    {
        $vault = new DotenvVault($this->tempDir . '/.env.local');
        $configWriter = new ConfigWriter($vault, $this->tempDir);

        $loader = new BuildIdLoader($configWriter, '');

        $result = $loader->loadEnvVars();

        self::assertArrayHasKey('SOLIDINVOICE_BUILD_ID', $result);
        self::assertTrue(Uuid::isValid($result['SOLIDINVOICE_BUILD_ID']));
    }

    public function testSealsBuildIdToVaultWhenNotSet(): void
    {
        $dotenvFile = $this->tempDir . '/.env.local';
        $vault = new DotenvVault($dotenvFile);
        $configWriter = new ConfigWriter($vault, $this->tempDir);

        $loader = new BuildIdLoader($configWriter, '');
        $result = $loader->loadEnvVars();

        self::assertFileExists($dotenvFile);
        $contents = file_get_contents($dotenvFile);
        self::assertStringContainsString('SOLIDINVOICE_BUILD_ID=', $contents);
        self::assertStringContainsString($result['SOLIDINVOICE_BUILD_ID'], $contents);
    }

    public function testReturnsSameValueOnlyOnce(): void
    {
        $vault = new DotenvVault($this->tempDir . '/.env.local');
        $configWriter = new ConfigWriter($vault, $this->tempDir);

        // First call with empty buildId generates a UUID
        $loader = new BuildIdLoader($configWriter, '');
        $result = $loader->loadEnvVars();
        $generatedId = $result['SOLIDINVOICE_BUILD_ID'];

        // Subsequent loader instance with the generated value is a no-op
        $loader2 = new BuildIdLoader($configWriter, $generatedId);
        self::assertSame([], $loader2->loadEnvVars());
    }
}
