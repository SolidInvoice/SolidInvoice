<?php

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