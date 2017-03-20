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

namespace CSBill\ClientBundle\Form\ViewTransformer;

use CSBill\ClientBundle\Entity\ContactType;
use Symfony\Component\Form\DataTransformerInterface;

class ContactTypeTransformer implements DataTransformerInterface
{
    /**
     * @var ContactType
     */
    private $type;

    /**
     * @param ContactType $type
     */
    public function __construct(ContactType $type)
    {
        $this->type = $type;
    }

    /**
     * @param mixed $value
     *
     * @return int
     */
    public function transform($value): int
    {
        return $this->type->getId();
    }

    /**
     * @param string $value
     *
     * @return \CSBill\ClientBundle\Entity\ContactType
     */
    public function reverseTransform(string $value): ContactType
    {
        return $this->type;
    }
}
