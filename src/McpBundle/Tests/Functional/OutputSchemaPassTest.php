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

use LogicException;
use Mcp\Capability\Discovery\SchemaValidator;
use Mcp\Capability\Registry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\McpBundle\DependencyInjection\McpOutputSchemaPass;
use SolidInvoice\McpBundle\Mcp\Output\CompanyInfoOutput;
use SolidInvoice\McpBundle\Mcp\Output\Entity\TaxOutput;
use SolidInvoice\McpBundle\Mcp\Output\OutputSchemaGenerator;
use SolidInvoice\McpBundle\Tests\Output\Fixtures\LineOutput;
use SolidInvoice\McpBundle\Tests\Output\Fixtures\ReturnTypeTools;
use stdClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

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

    public function testListToolGetsTheListEnvelopeSchema(): void
    {
        self::assertSame(
            new OutputSchemaGenerator()->generateList(TaxOutput::class),
            $this->registry()->getTool('list_tax_rates')->tool->outputSchema,
        );
    }

    public function testUnmarkedToolHasNoOutputSchema(): void
    {
        self::assertNull($this->registry()->getTool('get_dashboard_stats')->tool->outputSchema);
    }

    #[DataProvider('mismatchedReturnTypes')]
    public function testReturnTypeMustMatchDeclaredOutput(string $method): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessageIsOrContains(sprintf('%s::%s()', ReturnTypeTools::class, $method));

        $this->processTool($method);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function mismatchedReturnTypes(): iterable
    {
        yield 'other class' => ['otherClass'];
        yield 'nullable' => ['nullable'];
        yield 'no return type' => ['untyped'];
        yield 'list returning an item' => ['listReturningItem'];
        yield 'item returning a list' => ['itemReturningList'];
    }

    public function testMatchingReturnTypesPass(): void
    {
        self::assertSame(new OutputSchemaGenerator()->generate(LineOutput::class), $this->processTool('matching'));
        self::assertSame(new OutputSchemaGenerator()->generateList(LineOutput::class), $this->processTool('matchingList'));
    }

    /**
     * Runs the pass over a builder with a single addTool() call and returns the outputSchema it set.
     */
    private function processTool(string $method): mixed
    {
        $container = new ContainerBuilder();
        $builder = $container->register('mcp.server.test.builder', stdClass::class)
            ->addMethodCall('addTool', [[ReturnTypeTools::class, $method], null, null, null, null, null, null, null, null]);

        new McpOutputSchemaPass()->process($container);

        return $builder->getMethodCalls()[0][1][8];
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
