<?php

namespace CSBill\ClientBundle\Action\Ajax\Contact;

use CSBill\ClientBundle\Entity\Contact;
use CSBill\CoreBundle\Traits\DoctrineAwareTrait;
use CSBill\CoreBundle\Traits\JsonTrait;

class Delete
{
    use DoctrineAwareTrait,
        JsonTrait;

    public function __invoke(Contact $contact)
    {
        $em = $this->doctrine->getManager();
        $em->remove($contact);
        $em->flush();

        return $this->json([]);
    }
}