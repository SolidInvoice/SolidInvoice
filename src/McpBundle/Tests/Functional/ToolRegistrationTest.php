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

namespace SolidInvoice\McpBundle\Tests\Functional;

use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

/**
 * Asserts every Phase 2 tool class is registered with the `mcp.tool` tag in the
 * container. If one is missing, a business bundle is likely misconfigured.
 *
 * @group functional
 */
final class ToolRegistrationTest extends KernelTestCase
{
    use EnsureApplicationInstalled;
    use Factories;

    public function testAllReadToolClassesAreResolvable(): void
    {
        $expected = [
            \SolidInvoice\McpBundle\Mcp\Tool\ResourceQueryTools::class,
            \SolidInvoice\McpBundle\Mcp\Tool\WorkflowTools::class,
            \SolidInvoice\InvoiceBundle\Mcp\InvoiceReadTools::class,
            \SolidInvoice\QuoteBundle\Mcp\QuoteReadTools::class,
            \SolidInvoice\ClientBundle\Mcp\ClientReadTools::class,
            \SolidInvoice\PaymentBundle\Mcp\PaymentMethodReadTools::class,
            \SolidInvoice\TaxBundle\Mcp\TaxReadTools::class,
            \SolidInvoice\DashboardBundle\Mcp\AnalyticsTools::class,
            \SolidInvoice\SettingsBundle\Mcp\SettingsReadTools::class,
        ];

        foreach ($expected as $class) {
            $service = self::getContainer()->get($class);
            self::assertInstanceOf($class, $service, sprintf('Tool class %s is not resolvable.', $class));
        }
    }
}
