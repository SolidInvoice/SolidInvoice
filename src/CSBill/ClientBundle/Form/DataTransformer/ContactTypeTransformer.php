<?php

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
use CSBill\ClientBundle\Form\Type\ContactDetailType;
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
     * @param ContactDetailType $type
     *
     * @return string
     */
    public function transform($type)
    {
        if ($type) {
            return $type->getId();
        }
    }

    /**
     * @param string $value
     *
     * @return ContactType
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
