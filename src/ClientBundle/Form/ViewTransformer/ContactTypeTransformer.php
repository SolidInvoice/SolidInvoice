<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\ClientBundle\Form\ViewTransformer;

use SolidInvoice\ClientBundle\Entity\ContactType;
use Symfony\Component\Form\DataTransformerInterface;

class ContactTypeTransformer implements DataTransformerInterface
{
    /**
     * @var ContactType
     */
    private $type;

    public function __construct(ContactType $type)
    {
        $this->type = $type;
    }

    /**
     * @param mixed $value
     */
    public function transform($value): int
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
