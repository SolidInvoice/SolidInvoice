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
use SolidInvoice\InstallBundle\Step\GenerateSecretStep;
use Symfony\Bundle\FrameworkBundle\Secrets\DotenvVault;
use Symfony\Component\Filesystem\Filesystem;
use function sys_get_temp_dir;
use function uniqid;

/**
 * @covers \SolidInvoice\InstallBundle\Step\GenerateSecretStep
 */
final class GenerateSecretStepTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/solid_invoice_test_' . uniqid(more_entropy: true);
        mkdir($this->tempDir, 0777, true);
    }

    protected function tearDown(): void
    {
        $fs = new Filesystem();

        $fs->exists($this->tempDir) && $fs->remove($this->tempDir);
    }

    public function testPriority(): void
    {
        self::assertSame(30, GenerateSecretStep::priority());
    }

    public function testGetLabel(): void
    {
        self::assertSame('Generating secret', GenerateSecretStep::getLabel());
    }

    public function testExecuteGeneratesKeysAndSavesSecret(): void
    {
        $secretsDir = $this->tempDir . '/secrets';
        mkdir($secretsDir, 0777, true);
        $dotenvFile = $secretsDir . '/.env.local';

        $vault = new DotenvVault($dotenvFile);
        $configWriter = new ConfigWriter($vault, $secretsDir);

        $step = new GenerateSecretStep($vault, $configWriter);

        $installation = new Installation();

        $callbackMessages = [];
        $callback = static function (string $message) use (&$callbackMessages): \Generator {
            $callbackMessages[] = $message;
            yield;
        };

        $generator = $step->execute($installation, $callback);
        iterator_to_array($generator);

        self::assertCount(1, $callbackMessages);
        self::assertNotEmpty($callbackMessages[0]);
        // Verify the callback message doesn't contain the "; you can commit it" suffix
        self::assertStringNotContainsString('; you can commit it', $callbackMessages[0]);
    }

    public function testExecuteWithoutCallback(): void
    {
        $secretsDir = $this->tempDir . '/secrets2';
        mkdir($secretsDir, 0777, true);
        $dotenvFile = $secretsDir . '/.env.local';

        $vault = new DotenvVault($dotenvFile);
        $configWriter = new ConfigWriter($vault, $secretsDir);

        $step = new GenerateSecretStep($vault, $configWriter);

        $installation = new Installation();

        // Execute without callback - should not throw exception
        $generator = $step->execute($installation);
        self::assertSame([], iterator_to_array($generator));

        self::assertFileExists($dotenvFile);
        self::assertStringStartsWith("SOLIDINVOICE_APP_SECRET='def", file_get_contents($dotenvFile));
    }
}
