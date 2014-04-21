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

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Doctrine\ORM\EntityManager;

class ContactTypeTransformer implements DataTransformerInterface
{
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function transform($type)
    {
        if (null === $type) {
            return '';
        }

        return $type->getId();
    }

    public function reverseTransform($id)
    {
        if (!$id) {
            return null;
        }

        $type = $this->entityManager
            ->getRepository('CSBillClientBundle:ContactType')
            ->findOneById($id)
        ;

        if (null === $type) {
            throw new TransformationFailedException('Invalid contact type');
        }

        return $type;
    }
}
