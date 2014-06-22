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

use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class ContactTypeTransformer implements DataTransformerInterface
{
    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param mixed $type
     *
     * @return mixed|string
     */
    public function transform($type)
    {
        if (null === $type) {
            return '';
        }

        return $type->getId();
    }

    /**
     * @param mixed $id
     *
     * @return mixed|null
     */
    public function reverseTransform($id)
    {
        if (!$id) {
            return null;
        }

        $type = $this->registry
            ->getManager()
            ->getRepository('CSBillClientBundle:ContactType')
            ->findOneBy(array('id' => $id))
        ;

        if (null === $type) {
            throw new TransformationFailedException('Invalid contact type');
        }

        return $type;
    }
}
