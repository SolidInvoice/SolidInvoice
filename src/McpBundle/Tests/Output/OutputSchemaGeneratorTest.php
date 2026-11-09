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

namespace SolidInvoice\McpBundle\Tests\Output;

use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SolidInvoice\McpBundle\Mcp\Output\ListOutput;
use SolidInvoice\McpBundle\Mcp\Output\OutputSchemaGenerator;
use SolidInvoice\McpBundle\Tests\Output\Fixtures\EnumOutput;
use SolidInvoice\McpBundle\Tests\Output\Fixtures\LineOutput;
use SolidInvoice\McpBundle\Tests\Output\Fixtures\MixedOutput;
use SolidInvoice\McpBundle\Tests\Output\Fixtures\NestedOutput;
use SolidInvoice\McpBundle\Tests\Output\Fixtures\ScalarOutput;
use SolidInvoice\McpBundle\Tests\Output\Fixtures\UndocumentedArrayOutput;
use SolidInvoice\McpBundle\Tests\Output\Fixtures\UntypedOutput;

#[CoversClass(OutputSchemaGenerator::class)]
final class OutputSchemaGeneratorTest extends TestCase
{
    private const array LINE_SCHEMA = [
        'type' => 'object',
        'properties' => ['description' => ['type' => 'string']],
        'required' => ['description'],
        'additionalProperties' => false,
    ];

    public function testScalarProperties(): void
    {
        self::assertSame([
            'type' => 'object',
            'properties' => [
                'a' => ['type' => 'string'],
                'b' => ['type' => ['string', 'null']],
                'c' => ['type' => 'integer'],
                'd' => ['type' => 'boolean'],
                'e' => ['type' => 'number'],
            ],
            'required' => ['a', 'b', 'c', 'd', 'e'],
            'additionalProperties' => false,
        ], new OutputSchemaGenerator()->generate(ScalarOutput::class));
    }

    public function testBackedEnumProperties(): void
    {
        self::assertSame([
            'type' => 'object',
            'properties' => [
                'status' => ['type' => 'string', 'enum' => ['draft', 'paid']],
                'priority' => ['type' => ['integer', 'null'], 'enum' => [1, 2, null]],
            ],
            'required' => ['status', 'priority'],
            'additionalProperties' => false,
        ], new OutputSchemaGenerator()->generate(EnumOutput::class));
    }

    public function testNestedObjectsAndLists(): void
    {
        self::assertSame([
            'type' => 'object',
            'properties' => [
                'first' => ['type' => ['object', 'null']] + self::LINE_SCHEMA,
                'lines' => ['type' => 'array', 'items' => self::LINE_SCHEMA],
                'tags' => ['type' => 'array', 'items' => ['type' => 'string']],
            ],
            'required' => ['first', 'lines', 'tags'],
            'additionalProperties' => false,
        ], new OutputSchemaGenerator()->generate(NestedOutput::class));
    }

    public function testGenerateListWrapsItemsInEnvelope(): void
    {
        $schema = new OutputSchemaGenerator()->generateList(LineOutput::class);

        // The hand-built envelope must name exactly what a ListOutput encodes to.
        $encoded = json_decode(json_encode(new ListOutput([], 1, 10, 0), JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(array_keys($encoded), $schema['required']);
        self::assertSame([], $encoded['results']);

        self::assertSame([
            'type' => 'object',
            'properties' => [
                'results' => ['type' => 'array', 'items' => self::LINE_SCHEMA],
                'page' => ['type' => 'integer'],
                'items_per_page' => ['type' => 'integer'],
                'total' => ['type' => 'integer'],
            ],
            'required' => ['results', 'page', 'items_per_page', 'total'],
            'additionalProperties' => false,
        ], $schema);
    }

    /**
     * @param class-string $class
     */
    #[DataProvider('undescribableProperties')]
    public function testUndescribablePropertyThrows(string $class, string $property): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessageIsOrContains(sprintf('%s::$%s', $class, $property));

        new OutputSchemaGenerator()->generate($class);
    }

    /**
     * @return iterable<string, array{class-string, string}>
     */
    public static function undescribableProperties(): iterable
    {
        yield 'untyped' => [UntypedOutput::class, 'value'];
        yield 'mixed' => [MixedOutput::class, 'value'];
        yield 'array without item type' => [UndocumentedArrayOutput::class, 'values'];
    }
}
