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

final class DemoSaasMutualExclusionTest extends TestCase
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
        unset($_ENV['SOLIDINVOICE_DEMO'], $_ENV['SOLIDINVOICE_PLATFORM']);
    }

    public function testDemoAndSaasAreMutuallyExclusive(): void
    {
        $_SERVER['SOLIDINVOICE_DEMO'] = '1';
        $_SERVER['SOLIDINVOICE_PLATFORM'] = 'saas';
        unset($_ENV['SOLIDINVOICE_DEMO'], $_ENV['SOLIDINVOICE_PLATFORM']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Demo mode');

        require dirname(__DIR__, 4) . '/config/bundles.php';
    }

    public function testDemoWithoutSaasReturnsBundleMap(): void
    {
        $_SERVER['SOLIDINVOICE_DEMO'] = '1';
        unset($_SERVER['SOLIDINVOICE_PLATFORM'], $_ENV['SOLIDINVOICE_DEMO'], $_ENV['SOLIDINVOICE_PLATFORM']);

        $bundles = require dirname(__DIR__, 4) . '/config/bundles.php';

        self::assertIsArray($bundles);
    }
}
