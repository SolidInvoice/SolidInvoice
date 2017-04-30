<?php

namespace CSBill\ClientBundle\Action\Ajax\Address;

use CSBill\ClientBundle\Entity\Address;
use CSBill\ClientBundle\Form\Type\AddressType;
use CSBill\CoreBundle\Traits\DoctrineAwareTrait;
use CSBill\CoreBundle\Traits\JsonTrait;
use CSBill\CoreBundle\Traits\SaveableTrait;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;

class Delete
{
    use DoctrineAwareTrait,
        JsonTrait;

    public function __invoke(Address $address)
    {
        $em = $this->doctrine->getManager();
        /*$em->remove($address);
        $em->flush();*/

        return $this->json([]);
    }
}