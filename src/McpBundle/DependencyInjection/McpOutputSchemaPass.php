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

namespace SolidInvoice\McpBundle\DependencyInjection;

use LogicException;
use ReflectionMethod;
use SolidInvoice\McpBundle\Mcp\Output\McpOutput;
use SolidInvoice\McpBundle\Mcp\Output\OutputSchemaGenerator;
use Symfony\AI\McpBundle\DependencyInjection\LiteralArguments;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use function is_array;
use function sprintf;
use function str_ends_with;
use function str_starts_with;

/**
 * Fills the `outputSchema` (argument 8) of every `addTool()` call that
 * `Symfony\AI\McpBundle\DependencyInjection\McpPass` adds to an MCP server builder, for tool
 * methods marked with {@see McpOutput}. The schema is generated from the declared DTO, so the
 * DTO is the single source of truth for what the tool returns.
 */
final class McpOutputSchemaPass implements CompilerPassInterface
{
    private const int OUTPUT_SCHEMA_ARGUMENT = 8;

    public function process(ContainerBuilder $container): void
    {
        $generator = new OutputSchemaGenerator();

        foreach ($container->getDefinitions() as $id => $definition) {
            if (! str_starts_with($id, 'mcp.server.') || ! str_ends_with($id, '.builder')) {
                continue;
            }

            $calls = $definition->getMethodCalls();

            foreach ($calls as $index => [$method, $arguments]) {
                if ('addTool' !== $method || ! is_array($arguments[0] ?? null)) {
                    continue;
                }

                [$class, $toolMethod] = $arguments[0];
                $attributes = new ReflectionMethod($class, $toolMethod)->getAttributes(McpOutput::class);

                if ([] === $attributes) {
                    continue;
                }

                if (null !== ($arguments[self::OUTPUT_SCHEMA_ARGUMENT] ?? null)) {
                    throw new LogicException(sprintf('MCP tool %s::%s() sets outputSchema on #[McpTool] and also carries #[McpOutput]; use only #[McpOutput].', $class, $toolMethod));
                }

                $output = $attributes[0]->newInstance();
                $schema = $output->list ? $generator->generateList($output->class) : $generator->generate($output->class);
                $arguments[self::OUTPUT_SCHEMA_ARGUMENT] = LiteralArguments::escape($schema);
                $calls[$index][1] = $arguments;
            }

            $definition->setMethodCalls($calls);
        }
    }
}
