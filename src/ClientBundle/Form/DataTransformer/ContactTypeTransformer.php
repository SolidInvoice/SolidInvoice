<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\ClientBundle\Form\DataTransformer;

use CSBill\ClientBundle\Entity\ContactType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class ContactTypeTransformer implements DataTransformerInterface
{
    /**
     * @var ContactType[]
     */
    private $types;

    /**
     * @param array $types
     */
    public function __construct(array $types)
    {
        $this->types = $types;
    }

    /**
     * @param ContactType $type
     *
     * @return int
     */
    public function transform($type): ?int
    {
        if ($type) {
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
        if (!$value) {
            return null;
        }

        foreach ($this->types as $type) {
            if ($type->getId() === (int) $value) {
                return $type;
            }
        }

        throw new TransformationFailedException('Invalid contact type');
    }
}
