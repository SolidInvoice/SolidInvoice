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

namespace SolidInvoice\McpBundle\Mcp\Output;

use BackedEnum;
use LogicException;
use phpDocumentor\Reflection\Types\ContextFactory;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayTypeNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\NullableTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;
use PHPStan\PhpDocParser\ParserConfig;
use ReflectionClass;
use ReflectionEnum;
use ReflectionNamedType;
use ReflectionProperty;
use function array_filter;
use function array_keys;
use function array_map;
use function array_values;
use function class_exists;
use function count;
use function enum_exists;
use function explode;
use function in_array;
use function is_subclass_of;
use function ltrim;
use function sprintf;
use function strlen;
use function substr;

/**
 * Builds the JSON Schema of what `json_encode()` produces for an MCP output DTO: every public
 * property, in declaration order, all of them required (a null value is still present).
 *
 * Only draft-07-compatible keywords are emitted, so the schema validates under every protocol
 * revision. Array properties need an item type in a `@var` tag on the property or a `@param`
 * tag on the constructor (`list<LineOutput>`, `array<string>`, `LineOutput[]`).
 */
final class OutputSchemaGenerator
{
    private const array SCALARS = [
        'string' => 'string',
        'int' => 'integer',
        'bool' => 'boolean',
        'float' => 'number',
    ];

    private readonly Lexer $lexer;

    private readonly PhpDocParser $docParser;

    /**
     * @var array<string, true>
     */
    private array $generating = [];

    public function __construct()
    {
        $config = new ParserConfig([]);
        $constExprParser = new ConstExprParser($config);
        $this->lexer = new Lexer($config);
        $this->docParser = new PhpDocParser($config, new TypeParser($config, $constExprParser), $constExprParser);
    }

    /**
     * @param class-string $class
     *
     * @return array<string, mixed>
     */
    public function generate(string $class): array
    {
        if (isset($this->generating[$class])) {
            throw new LogicException(sprintf('Cannot generate an MCP output schema for %s: it contains itself.', $class));
        }

        $this->generating[$class] = true;

        try {
            $properties = [];

            foreach (new ReflectionClass($class)->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
                if (! $property->isStatic()) {
                    $properties[$property->getName()] = $this->propertySchema($property);
                }
            }
        } finally {
            unset($this->generating[$class]);
        }

        if ([] === $properties) {
            throw new LogicException(sprintf('Cannot generate an MCP output schema for %s: it has no public properties.', $class));
        }

        return $this->objectSchema($properties);
    }

    /**
     * The schema of a {@see ListOutput} whose results are $itemClass items.
     *
     * @param class-string $itemClass
     *
     * @return array<string, mixed>
     */
    public function generateList(string $itemClass): array
    {
        return $this->objectSchema([
            'results' => ['type' => 'array', 'items' => $this->generate($itemClass)],
            'page' => ['type' => 'integer'],
            'items_per_page' => ['type' => 'integer'],
            'total' => ['type' => 'integer'],
        ]);
    }

    /**
     * @param array<string, array<string, mixed>> $properties
     *
     * @return array<string, mixed>
     */
    private function objectSchema(array $properties): array
    {
        return [
            'type' => 'object',
            'properties' => $properties,
            'required' => array_keys($properties),
            'additionalProperties' => false,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function propertySchema(ReflectionProperty $property): array
    {
        $type = $property->getType();

        if (! $type instanceof ReflectionNamedType || 'mixed' === $type->getName() || 'null' === $type->getName()) {
            throw $this->undescribable($property, 'it needs a single native type (nullable allowed)');
        }

        $schema = 'array' === $type->getName()
            ? ['type' => 'array', 'items' => $this->typeSchema($this->itemType($property), $property)]
            : $this->typeSchema($type->getName(), $property);

        if (! $type->allowsNull()) {
            return $schema;
        }

        $schema['type'] = [$schema['type'], 'null'];

        if (isset($schema['enum'])) {
            $schema['enum'][] = null;
        }

        return $schema;
    }

    /**
     * @return array<string, mixed>
     */
    private function typeSchema(string $type, ReflectionProperty $property): array
    {
        if (isset(self::SCALARS[$type])) {
            return ['type' => self::SCALARS[$type]];
        }

        if (enum_exists($type) && is_subclass_of($type, BackedEnum::class)) {
            $backing = (string) new ReflectionEnum($type)->getBackingType();

            return [
                'type' => self::SCALARS[$backing],
                'enum' => array_map(static fn (BackedEnum $case): int | string => $case->value, $type::cases()),
            ];
        }

        if (class_exists($type)) {
            return $this->generate($type);
        }

        throw $this->undescribable($property, sprintf('type "%s" cannot be described', $type));
    }

    /**
     * Resolves the item type of an array property from its docblock, to a scalar name or FQCN.
     */
    private function itemType(ReflectionProperty $property): string
    {
        $docType = $this->docType($property);

        if ($docType instanceof NullableTypeNode) {
            $docType = $docType->type;
        }

        if ($docType instanceof UnionTypeNode) {
            $types = array_values(array_filter(
                $docType->types,
                static fn (TypeNode $type): bool => ! $type instanceof IdentifierTypeNode || 'null' !== $type->name,
            ));
            $docType = 1 === count($types) ? $types[0] : null;
        }

        $item = match (true) {
            $docType instanceof ArrayTypeNode => $docType->type,
            $docType instanceof GenericTypeNode
                && in_array($docType->type->name, ['list', 'array', 'non-empty-list', 'non-empty-array'], true)
                && 1 === count($docType->genericTypes) => $docType->genericTypes[0],
            default => null,
        };

        if (! $item instanceof IdentifierTypeNode) {
            throw $this->undescribable($property, 'an array property needs a "list<X>", "array<X>" or "X[]" item type in its docblock');
        }

        if (isset(self::SCALARS[$item->name])) {
            return $item->name;
        }

        return $this->resolveClassName($item->name, $property->getDeclaringClass());
    }

    private function docType(ReflectionProperty $property): ?TypeNode
    {
        $propertyDoc = $property->getDocComment();

        if (false !== $propertyDoc) {
            foreach ($this->parseDoc($propertyDoc)->getVarTagValues() as $tag) {
                return $tag->type;
            }
        }

        $constructorDoc = $property->isPromoted() ? $property->getDeclaringClass()->getConstructor()?->getDocComment() : false;

        if (false === $constructorDoc || null === $constructorDoc) {
            return null;
        }

        foreach ($this->parseDoc($constructorDoc)->getParamTagValues() as $tag) {
            if ('$' . $property->getName() === $tag->parameterName) {
                return $tag->type;
            }
        }

        return null;
    }

    private function parseDoc(string $doc): PhpDocNode
    {
        return $this->docParser->parse(new TokenIterator($this->lexer->tokenize($doc)));
    }

    /**
     * @param ReflectionClass<object> $declaringClass
     */
    private function resolveClassName(string $name, ReflectionClass $declaringClass): string
    {
        if ('\\' === $name[0]) {
            return substr($name, 1);
        }

        $context = new ContextFactory()->createFromReflector($declaringClass);
        [$first] = explode('\\', $name, 2);
        $alias = $context->getNamespaceAliases()[$first] ?? null;

        if (null !== $alias) {
            return ltrim($alias, '\\') . substr($name, strlen($first));
        }

        return ltrim($context->getNamespace() . '\\' . $name, '\\');
    }

    private function undescribable(ReflectionProperty $property, string $reason): LogicException
    {
        return new LogicException(sprintf(
            'Cannot generate an MCP output schema for %s::$%s: %s.',
            $property->getDeclaringClass()->getName(),
            $property->getName(),
            $reason,
        ));
    }
}
