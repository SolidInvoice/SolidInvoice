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

use PHPUnit\Framework\TestCase;
use RuntimeException;
use SolidInvoice\SaasBundle\SolidInvoiceSaasBundle;
use SolidWorx\Platform\SaasBundle\SolidWorxPlatformSaasBundle;

final class ModeBootValidationTest extends TestCase
{
    /**
     * @var array<string, mixed>
     */
    private array $originalServer = [];

    protected function setUp(): void
    {
        $this->originalServer = $_SERVER;
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->originalServer;
        unset($_ENV['SOLIDINVOICE_MODE'], $_ENV['SOLIDINVOICE_DEMO_USERNAME'], $_ENV['SOLIDINVOICE_DEMO_PASSWORD']);
    }

    public function testUnknownModeThrows(): void
    {
        $_SERVER['SOLIDINVOICE_MODE'] = 'bogus';
        unset($_SERVER['SOLIDINVOICE_DEMO_USERNAME'], $_SERVER['SOLIDINVOICE_DEMO_PASSWORD'], $_ENV['SOLIDINVOICE_MODE']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid SOLIDINVOICE_MODE');

        require dirname(__DIR__, 4) . '/config/bundles.php';
    }

    public function testDemoModeWithoutCredentialsThrows(): void
    {
        $_SERVER['SOLIDINVOICE_MODE'] = 'demo';
        unset(
            $_SERVER['SOLIDINVOICE_DEMO_USERNAME'],
            $_SERVER['SOLIDINVOICE_DEMO_PASSWORD'],
            $_ENV['SOLIDINVOICE_MODE'],
            $_ENV['SOLIDINVOICE_DEMO_USERNAME'],
            $_ENV['SOLIDINVOICE_DEMO_PASSWORD'],
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('SOLIDINVOICE_MODE=demo requires');

        require dirname(__DIR__, 4) . '/config/bundles.php';
    }

    public function testDemoModeWithCredentialsReturnsBundleMap(): void
    {
        $_SERVER['SOLIDINVOICE_MODE'] = 'demo';
        $_SERVER['SOLIDINVOICE_DEMO_USERNAME'] = 'demo';
        $_SERVER['SOLIDINVOICE_DEMO_PASSWORD'] = 'demo';
        unset($_ENV['SOLIDINVOICE_MODE'], $_ENV['SOLIDINVOICE_DEMO_USERNAME'], $_ENV['SOLIDINVOICE_DEMO_PASSWORD']);

        $bundles = require dirname(__DIR__, 4) . '/config/bundles.php';

        self::assertIsArray($bundles);
    }

    public function testSaasModeRegistersSaasBundles(): void
    {
        $_SERVER['SOLIDINVOICE_MODE'] = 'saas';
        unset($_SERVER['SOLIDINVOICE_DEMO_USERNAME'], $_SERVER['SOLIDINVOICE_DEMO_PASSWORD'], $_ENV['SOLIDINVOICE_MODE']);

        $bundles = require dirname(__DIR__, 4) . '/config/bundles.php';

        self::assertArrayHasKey(SolidWorxPlatformSaasBundle::class, $bundles);
        self::assertArrayHasKey(SolidInvoiceSaasBundle::class, $bundles);
    }
}
