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

namespace SolidInvoice\CoreBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use function is_object;
use function method_exists;

/**
 * @implements DataTransformerInterface<int, object|string>
 */
final class EntityUuidTransformer implements DataTransformerInterface
{
    /**
     * @param object[] $types
     */
    public function __construct(
        private readonly array $types
    ) {
    }

    /**
     * @param ?object $value
     */
    public function transform($value): ?string
    {
        if (is_object($value) && method_exists($value, 'getId')) {
            return $value->getId()->toString();
        }

        return null;
    }

    /**
     * @param object|string|null $value
     */
    public function reverseTransform($value): ?object
    {
        if ('' === $value || null === $value) {
            return null;
        }

        foreach ($this->types as $type) {
            if ($type->getId()->toString() === $value) {
                return $type;
            }
        }

        throw new TransformationFailedException('Invalid value');
    }
}
