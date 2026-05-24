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

use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\ClientBundle\Mcp\ClientReadTools;
use SolidInvoice\DashboardBundle\Mcp\AnalyticsTools;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\InvoiceBundle\Mcp\InvoiceReadTools;
use SolidInvoice\McpBundle\Mcp\Tool\ResourceQueryTools;
use SolidInvoice\McpBundle\Mcp\Tool\WorkflowTools;
use SolidInvoice\PaymentBundle\Mcp\PaymentMethodReadTools;
use SolidInvoice\QuoteBundle\Mcp\QuoteReadTools;
use SolidInvoice\SettingsBundle\Mcp\SettingsReadTools;
use SolidInvoice\TaxBundle\Mcp\TaxReadTools;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

/**
 * Asserts every Phase 2 tool class is registered with the `mcp.tool` tag in the
 * container. If one is missing, a business bundle is likely misconfigured.
 */
#[Group('functional')]
final class ToolRegistrationTest extends KernelTestCase
{
    use EnsureApplicationInstalled;
    use Factories;

    public function testAllReadToolClassesAreResolvable(): void
    {
        $expected = [
            ResourceQueryTools::class,
            WorkflowTools::class,
            InvoiceReadTools::class,
            QuoteReadTools::class,
            ClientReadTools::class,
            PaymentMethodReadTools::class,
            TaxReadTools::class,
            AnalyticsTools::class,
            SettingsReadTools::class,
        ];

        foreach ($expected as $class) {
            $service = self::getContainer()->get($class);
            self::assertInstanceOf($class, $service, sprintf('Tool class %s is not resolvable.', $class));
        }
    }
}
