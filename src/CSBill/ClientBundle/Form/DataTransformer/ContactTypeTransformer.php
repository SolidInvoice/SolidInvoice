<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\ClientBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class ContactTypeTransformer implements DataTransformerInterface
{
    /**
     * @var \CSBill\ClientBundle\Entity\ContactType[]
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
     * @param mixed $type
     *
     * @return string
     */
    public function transform($type)
    {
        if (null !== $type) {
            return $type->getId();
        }

        return;
    }

    /**
     * @param string $value
     *
     * @return \CSBill\ClientBundle\Entity\ContactType
     *
     * @throws TransformationFailedException
     */
    public function reverseTransform($value)
    {
        if (!$value) {
            return;
        }

        foreach ($this->types as $type) {
            if ($type->getId() === (int) $value) {
                return $type;
            }
        }

        throw new TransformationFailedException('Invalid contact type');
    }
}
