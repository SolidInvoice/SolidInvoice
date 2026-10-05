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

namespace SolidInvoice\EInvoiceBundle\Tests\Channel;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionIntersectionType;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionType;
use ReflectionUnionType;
use SolidInvoice\EInvoiceBundle\Channel\ChannelInterface;
use SolidInvoice\EInvoiceBundle\Channel\TransmissionResult;
use SolidInvoice\EInvoiceBundle\Entity\EInvoiceDocument;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * #2677's acceptance criteria ask for the no-provider-type and no-syntax-type rules to be
 * "asserted by review". A review assertion decays; this makes the boundary structural. Any
 * sixth method, or any new type in a signature, fails this test until someone consciously
 * edits the allow-list below — which is the review moment the criterion asks for, now
 * unforgettable.
 */
final class ChannelInterfaceBoundaryTest extends TestCase
{
    private const array ALLOWED_METHODS = ['getIdentifier', 'getLabel', 'supports', 'send', 'poll'];

    private const array ALLOWED_TYPES = [
        'string',
        'bool',
        EInvoiceDocument::class,
        TransmissionResult::class,
        TranslatableInterface::class,
    ];

    public function testInterfaceDeclaresExactlyTheExpectedMethods(): void
    {
        $methods = array_map(
            static fn (ReflectionMethod $method): string => $method->getName(),
            new ReflectionClass(ChannelInterface::class)->getMethods(),
        );

        sort($methods);
        $expected = self::ALLOWED_METHODS;
        sort($expected);

        self::assertSame($expected, $methods);
    }

    public function testEveryParameterAndReturnTypeIsOnTheAllowList(): void
    {
        $reflection = new ReflectionClass(ChannelInterface::class);

        foreach ($reflection->getMethods() as $method) {
            foreach ($method->getParameters() as $parameter) {
                $type = $parameter->getType();
                self::assertNotNull($type, sprintf('%s::$%s has no declared type.', $method->getName(), $parameter->getName()));

                foreach ($this->namedConstituents($type) as $name) {
                    self::assertContains(
                        $name,
                        self::ALLOWED_TYPES,
                        sprintf('%s::$%s uses disallowed type "%s".', $method->getName(), $parameter->getName(), $name),
                    );
                }
            }

            $returnType = $method->getReturnType();
            self::assertNotNull($returnType, sprintf('%s() has no declared return type.', $method->getName()));

            foreach ($this->namedConstituents($returnType) as $name) {
                self::assertContains(
                    $name,
                    self::ALLOWED_TYPES,
                    sprintf('%s() returns disallowed type "%s".', $method->getName(), $name),
                );
            }
        }
    }

    /**
     * Resolves a (possibly nullable, union or intersection) reflection type down to its
     * named constituents, dropping `null` — a `?Foo` parameter allows `Foo`, and this test
     * cares about the provider-facing types, not nullability.
     *
     * @return list<string>
     */
    private function namedConstituents(ReflectionType $type): array
    {
        if ($type instanceof ReflectionNamedType) {
            return $type->getName() === 'null' ? [] : [$type->getName()];
        }

        if ($type instanceof ReflectionUnionType || $type instanceof ReflectionIntersectionType) {
            $names = [];

            foreach ($type->getTypes() as $inner) {
                $names = [...$names, ...$this->namedConstituents($inner)];
            }

            return $names;
        }

        self::fail(sprintf('Unhandled reflection type: %s', $type::class));
    }
}
