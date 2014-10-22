<?php
/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
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
     * @return string
     */
    public function transform($value)
    {
        return $this->type->getId();
    }

    /**
     * @param string $value
     *
     * @return \CSBill\ClientBundle\Entity\ContactType
     */
    public function reverseTransform($value)
    {
        return $this->type;
    }
}
