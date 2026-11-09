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

use Mcp\Capability\Discovery\SchemaValidator;
use Mcp\Capability\Registry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\McpBundle\DependencyInjection\McpOutputSchemaPass;
use SolidInvoice\McpBundle\Mcp\Output\CompanyInfoOutput;
use SolidInvoice\McpBundle\Mcp\Output\OutputSchemaGenerator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[CoversClass(McpOutputSchemaPass::class)]
#[Group('functional')]
final class OutputSchemaPassTest extends KernelTestCase
{
    use EnsureApplicationInstalled;

    public function testMarkedToolGetsGeneratedOutputSchema(): void
    {
        self::assertSame(
            new OutputSchemaGenerator()->generate(CompanyInfoOutput::class),
            $this->registry()->getTool('get_company_info')->tool->outputSchema,
        );
    }

    public function testPilotOutputConformsToItsSchema(): void
    {
        $tool = $this->registry()->getTool('get_company_info');
        self::assertNotNull($tool->tool->outputSchema);

        // Validated the way CallToolHandler does it; a null currency must still conform.
        foreach ([new CompanyInfoOutput('id', 'Acme', 'USD'), new CompanyInfoOutput('id', 'Acme', null)] as $output) {
            $sent = json_decode(json_encode($tool->extractStructuredContent($output), JSON_THROW_ON_ERROR), false, 512, JSON_THROW_ON_ERROR);
            self::assertSame([], new SchemaValidator()->validateAgainstJsonSchema($sent, $tool->tool->outputSchema));
        }
    }

    public function testUnmarkedToolHasNoOutputSchema(): void
    {
        self::assertNull($this->registry()->getTool('list_tax_rates')->tool->outputSchema);
    }

    private function registry(): Registry
    {
        // Building the server is what loads the builder's addTool() calls into the registry.
        self::getContainer()->get('mcp.server.solidinvoice');
        $registry = self::getContainer()->get('mcp.server.solidinvoice.registry');
        self::assertInstanceOf(Registry::class, $registry);

        return $registry;
    }
}
