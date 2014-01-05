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
        return new JsonResponse(
            array(
                "content" => $this->renderView('CSBillClientBundle:Ajax:info.html.twig',
                        array(
                            'client' => $client
                        )
                    )
            )
        );
    }

    /**
     * Add a new contact to a client
     *
     * @param  Client                                                        $client
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function addcontactAction(Client $client)
    {
        $request = $this->getRequest();

        if (!$request->isXmlHttpRequest()) {
            throw $this->createNotFoundException();
        }

        $contact = new Contact();
        $contact->setClient($client);

        $form = $this->createForm('contact', $contact);

        $response = array();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $entityManager = $this->getEm();

            $entityManager->persist($contact);
            $entityManager->flush();

            return new JsonResponse(array(
                "status"    => "success",
                "content"   => $this->renderView('CSBillClientBundle:Ajax:contact_add.html.twig',
                        array(
                            'contact' => $contact
                        )
                    ),
                "id"        => $contact->getId()
            ));
        } else {
            $response['status'] = 'failure';
        }

        $response["content"] = $this->renderView('CSBillClientBundle:Ajax:contact_add.html.twig',
            array(
                'form' => $form->createView(),
                'client' => $client
            )
        );

        return new JsonResponse($response);
    }

    /**
     * Edits a contact
     *
     * @param  Contact                                                       $contact
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function editcontactAction(Contact $contact)
    {
        $request = $this->getRequest();

        if (!$request->isXmlHttpRequest()) {
            throw $this->createNotFoundException();
        }

        $originalContactDetails = $contact->getDetails()->toArray();

        $form = $this->createForm('contact', $contact);

        if ($request->isMethod('POST')) {
            $contact->getDetails()->clear();
        }

        $form->handleRequest($request);

        if ($form->isValid()) {

            $entityManager = $this->getEm();

            foreach ($contact->getDetails() as $detail) {
                /** @var \CSBill\ClientBundle\Entity\ContactDetail $detail */
                foreach ($originalContactDetails as $key => $toDel) {
                    //var_dump($toDel->getId(), $detail->getId());
                    /** @var \CSBill\ClientBundle\Entity\ContactDetail $toDel */
                    if ($toDel->getId() === $detail->getId()) {
                        unset($originalContactDetails[$key]);
                    }
                }
            }

            unset($detail);

            foreach ($originalContactDetails as $detail) {
                $contact->removeDetail($detail);
                $entityManager->remove($detail);
            }

            $entityManager->persist($contact);
            $entityManager->flush();

            return new JsonResponse(
                array(
                    "content" => $this->renderView('CSBillClientBundle:Ajax:contact_edit.html.twig',
                            array(
                                'success' => true
                            )
                        ),
                    "status" => "success"
                )
            );
        }

        return new JsonResponse(
            array(
                "content" => $this->renderView('CSBillClientBundle:Ajax:contact_edit.html.twig',
                        array(
                            'form' => $form->createView(),
                            'contact' => $contact
                        )
                    )
            )
        );
    }

    /**
     * Renders a contact card
     *
     * @param  Contact                                    $contact
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function contactcardAction(Contact $contact)
    {
        return new JsonResponse(
            array(
                'content' => $this->renderView('CSBillClientBundle::contact_card.html.twig',
                        array(
                            'contact' => $contact
                        )
                    )
            )
        );
    }

    /**
     * Deletes a contact
     *
     * @param  Contact      $contact
     * @return JsonResponse
     */
    public function deletecontactAction(Contact $contact)
    {
        $entityMnager = $this->getEm();
        $entityMnager->remove($contact);
        $entityMnager->flush();

        return new JsonResponse(array("status" => "success"));
    }

    /**
     * Deletes a client
     *
     * @param  Client       $client
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
