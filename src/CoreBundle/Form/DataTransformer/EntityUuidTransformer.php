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

use Ramsey\Uuid\UuidInterface;
use SolidInvoice\ClientBundle\Entity\ContactType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use function method_exists;

/**
 * @implements DataTransformerInterface<int, object|string>
 */
final class EntityUuidTransformer implements DataTransformerInterface
{
    /**
     * @var object[]
     */
    private array $types;

    /**
     * @param object[] $types
     */
    public function __construct(array $types)
    {
        $this->types = $types;
    }

    /**
     * @param ?object $value
     */
    public function transform($value): ?UuidInterface
    {
        if ($value instanceof ContactType) {
            return $value->getId();
        }

        return null;
    }

    /**
     * @param object|string $value
     *
     * @return object
     *
     * @throws TransformationFailedException
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
