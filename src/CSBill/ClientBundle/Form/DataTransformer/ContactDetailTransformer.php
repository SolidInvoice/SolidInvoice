<?php
/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\ClientBundle\Form\DataTransformer;

use CSBill\ClientBundle\Entity\ContactDetail;
use CSBill\ClientBundle\Entity\ContactType;
use CSBill\ClientBundle\Entity\PrimaryContactDetail;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class ContactDetailTransformer implements DataTransformerInterface
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
     * @param ContactDetail[] $value
     *
     * @return ContactDetail|null
     */
    public function transform($value)
    {
        if (null !== $value) {
            foreach ($value as $detail) {
                if ($detail->getType() === $this->type) {
                    return $detail;
                }
            }
        }

        return;
    }

    /**
     * @param string $value
     *
     * @return ContactDetail
     * @throws TransformationFailedException
     */
    public function reverseTransform($value)
    {
        if ($value instanceof PrimaryContactDetail && $value->getType() === $this->type) {
            return $value;
        }

        throw new TransformationFailedException();
    }
}
