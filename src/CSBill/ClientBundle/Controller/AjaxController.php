<?php

/*
 * This file is part of the CSBill package.
 *
 * (c) Pierre du Plessis <info@customscripts.co.za>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CSBill\ClientBundle\Controller;

use CS\CoreBundle\Controller\Controller;
use CSBill\ClientBundle\Entity\Client;
use CSBill\ClientBundle\Entity\Contact;
use CSBill\ClientBundle\Form\Contact as ContactForm;
use Symfony\Component\HttpFoundation\JsonResponse;

class AjaxController extends Controller
{

    /**
     * Get client info
     *
     * @param  Client                                     $client
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function infoAction(Client $client)
    {
        return new JsonResponse(array("content" => $this->renderView('CSBillClientBundle:Ajax:info.html.twig', array('client' => $client))));
    }

    /**
     * Add a new contact to a client
     *
     * @param  Client                                     $client
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function addcontactAction(Client $client)
    {
        $contact = new Contact();
        $contact->setClient($client);

        $form = $this->createForm(new ContactForm(), $contact);

        $request = $this->getRequest();

        $response = array();

        if ($request->isXmlHttpRequest() && $request->isMethod('POST')) {
            $form->bind($request);

            if ($form->isValid()) {
                $em = $this->getEm();

                $em->persist($contact);
                $em->flush();

                return new JsonResponse(array(
                    "status"    => "success",
                    "content"   => $this->renderView('CSBillClientBundle:Ajax:contact_add.html.twig', array('contact' => $contact)),
                    "id"        => $contact->getId()
                ));
            } else {
                $response['status'] = 'failure';
            }
        }

        $response["content"] = $this->renderView('CSBillClientBundle:Ajax:contact_add.html.twig', array('form' => $form->createView(), 'client' => $client));

        return new JsonResponse($response);
    }

    /**
     * Edits a contact
     *
     * @param  Contact                                    $contact
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function editcontactAction(Contact $contact)
    {
        $form = $this->createForm(new ContactForm(), $contact);

        $request = $this->getRequest();

        if ($request->isXmlHttpRequest() && $request->isMethod('POST')) {

            $originalContactDetails = $contact->getDetails()->toArray();

            $form->bind($request);

            if ($form->isValid()) {
                $em = $this->getEm();

                // filter $originalTags to contain tags no longer present
                foreach ($contact->getDetails() as $detail) {
                    /** @var \CSBill\ClientBundle\Entity\ContactDetail $detail */
                    foreach ($originalContactDetails as $key => $toDel) {
                        /** @var \CSBill\ClientBundle\Entity\ContactDetail $toDel */
                        if ($toDel->getId() === $detail->getId()) {
                            unset($originalContactDetails[$key]);
                        }
                    }
                }

                // remove the relationship between the tag and the Task
                foreach ($originalContactDetails as $detail) {
                    // remove the Task from the Tag
                    $contact->removeDetail($detail);
                }

                $em->persist($contact);
                $em->flush();

                return new JsonResponse(array(
                            "content" => $this->renderView('CSBillClientBundle:Ajax:contact_edit.html.twig', array('success' => true)),
                            "status" => "success"
                        ));
            }
        }

        return new JsonResponse(array(
                    "content" => $this->renderView('CSBillClientBundle:Ajax:contact_edit.html.twig', array('form' => $form->createView(), 'contact' => $contact))
                ));
    }

    /**
     * Renders a contact card
     *
     * @param  Contact                                    $contact
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function contactcardAction(Contact $contact)
    {
        return new JsonResponse(array('content' => $this->renderView('CSBillClientBundle::contact_card.html.twig', array('contact' => $contact))));
    }

    /**
     * Deletes a contact
     *
     * @param Contact $contact
     * @return JsonResponse
     */
    public function deletecontactAction(Contact $contact)
    {
        $em = $this->getEm();
        $em->remove($contact);
        $em->flush();

        return new JsonResponse(array("status" => "success"));
    }

    /**
     * Deletes a client
     *
     * @param Client $client
     * @return JsonResponse
     */
    public function deleteclientAction(Client $client)
    {
        $em = $this->getEm();
        $em->remove($client);
        $em->flush();

        $this->flash($this->trans('The client was deleted successfully'));

        return new JsonResponse(array("status" => "success"));
    }
}
