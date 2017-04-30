<?php

namespace CSBill\ClientBundle\Action\Ajax\Contact;

use CSBill\ClientBundle\Entity\Client;
use CSBill\ClientBundle\Entity\Contact;
use CSBill\ClientBundle\Form\Handler\ContactAddFormHandler;
use SolidWorx\FormHandler\FormHandler;
use Symfony\Component\HttpFoundation\Request;

class Add
{
    /**
     * @var FormHandler
     */
    private $handler;

    public function __construct(FormHandler $handler)
    {
        $this->handler = $handler;
    }

    public function __invoke(Request $request, Client $client)
    {
        $contact = new Contact();
        $contact->setClient($client);

        return $this->handler->handle(ContactAddFormHandler::class, $contact);
    }
}