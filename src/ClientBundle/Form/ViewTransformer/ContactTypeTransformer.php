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

namespace SolidInvoice\ClientBundle\Form\ViewTransformer;

use Ramsey\Uuid\UuidInterface;
use SolidInvoice\ClientBundle\Entity\ContactType;
use Symfony\Component\Form\DataTransformerInterface;

class ContactTypeTransformer implements DataTransformerInterface
{
    private ContactType $type;

    public function __construct(ContactType $type)
    {
        $this->type = $type;
    }

    /**
     * @param mixed $value
     */
    public function transform($value): UuidInterface
    {
        return $this->type->getId();
    }

    /**
     * @param string $value
     */
    public function reverseTransform($value): ContactType
    {
        return $this->type;
    }
}
