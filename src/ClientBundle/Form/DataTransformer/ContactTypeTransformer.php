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

namespace SolidInvoice\ClientBundle\Form\DataTransformer;

use Ramsey\Uuid\UuidInterface;
use SolidInvoice\ClientBundle\Entity\ContactType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class ContactTypeTransformer implements DataTransformerInterface
{
    /**
     * @var ContactType[]
     */
    private array $types;

    /**
     * @param ContactType[] $types
     */
    public function __construct(array $types)
    {
        $this->types = $types;
    }

    /**
     * @param ?ContactType $type
     */
    public function transform($type): ?UuidInterface
    {
        if ($type instanceof ContactType) {
            return $type->getId();
        }

        return null;
    }

    /**
     * @param string $value
     *
     * @return ContactType
     *
     * @throws TransformationFailedException
     */
    public function reverseTransform($value): ?ContactType
    {
        if ('' === $value) {
            return null;
        }

        foreach ($this->types as $type) {
            if ($type->getId()->toString() === $value) {
                return $type;
            }
        }

        throw new TransformationFailedException('Invalid contact type');
    }
}
