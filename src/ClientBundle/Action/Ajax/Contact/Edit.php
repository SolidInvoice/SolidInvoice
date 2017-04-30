<?php

namespace CSBill\ClientBundle\Action\Ajax\Contact;

use CSBill\ClientBundle\Entity\Contact;
use CSBill\ClientBundle\Form\Handler\ContactEditFormHandler;
use SolidWorx\FormHandler\FormHandler;
use Symfony\Component\HttpFoundation\Request;

class Edit
{
    /**
     * @var FormHandler
     */
    private $handler;

    public function __construct(FormHandler $handler)
    {
        $this->handler = $handler;
    }

    public function __invoke(Request $request, Contact $contact)
    {
        return $this->handler->handle(ContactEditFormHandler::class, $contact);
        /*$status = 'success';

        $originalContactDetails = $contact->getAdditionalContactDetails()->toArray();

        $form = $this->createForm(ContactType::class, $contact, ['allow_delete' => false]);

        if ($request->isMethod('POST')) {
            $contact->getAdditionalDetails()->clear();
        }

        $form->handleRequest($request);

        if ($form->isValid()) {
            $this->removeContactDetails($contact, $originalContactDetails);

            $this->save($contact);
        } elseif ($form->isSubmitted()) {
            $status = 'failure';
        }

        return $this->json(
            [
                'content' => $this->renderView(
                    'CSBillClientBundle:Ajax:contact_edit.html.twig',
                    [
                        'form' => $form->createView(),
                        'contact' => $contact,
                    ]
                ),
                'status' => $status,
            ]
        );*/
    }
}