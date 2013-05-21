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
use CSBill\ClientBundle\Form\Type\ContactType;
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
        return $this->render('CSBillClientBundle:Ajax:info.html.twig', array('client' => $client));
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

        $form = $this->createForm(new ContactType(), $contact);

        $request = $this->getRequest();

        if ($request->isXmlHttpRequest() && $request->isMethod('POST')) {
            $form->bind($request);

            if ($form->isValid()) {
                $em = $this->getEm();

                $em->persist($contact);
                $em->flush();

                return $this->render('CSBillClientBundle:Ajax:contact_add.html.twig', array('contact' => $contact));
            }
        }

        return $this->render('CSBillClientBundle:Ajax:contact_add.html.twig', array('form' => $form->createView(), 'client' => $client));

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
    }
}
