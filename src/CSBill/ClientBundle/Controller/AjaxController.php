<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\ClientBundle\Controller;

use CSBill\ClientBundle\Entity\Client;
use CSBill\ClientBundle\Entity\Contact;
use CSBill\CoreBundle\Controller\BaseController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class AjaxController extends BaseController
{
    /**
     * Get client info.
     *
     * @param Client $client
     * @param string $type
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function infoAction(Client $client, $type = 'quote')
    {
        $content = $this->renderView(
            'CSBillClientBundle:Ajax:info.html.twig',
            array(
                'client' => $client,
                'type' => $type,
            )
        );

        return $this->json(array('content' => $content));
    }

    /**
     * Add a new contact to a client.
     *
     * @param Request $request
     * @param Client  $client
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function addcontactAction(Request $request, Client $client)
    {
        if (!$request->isXmlHttpRequest()) {
            throw $this->createNotFoundException();
        }

        $contact = new Contact();
        $contact->setClient($client);

        $form = $this->createForm('contact', $contact, array('allow_delete' => false));

        $response = array();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $this->save($contact);

            $content = $this->renderView(
                'CSBillClientBundle:Ajax:contact_add.html.twig',
                array(
                    'contact' => $contact,
                )
            );

            return $this->json(
                array(
                    'status' => 'success',
                    'content' => $content,
                    'id' => $contact->getId(),
                )
            );
        } else {
            $response['status'] = 'failure';
        }

        $content = $this->renderView(
            'CSBillClientBundle:Ajax:contact_add.html.twig',
            array(
                'form' => $form->createView(),
                'client' => $client,
            )
        );
        $response['content'] = $content;

        return $this->json($response);
    }

    /**
     * Edits a contact.
     *
     * @param Request $request
     * @param Contact $contact
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function editcontactAction(Request $request, Contact $contact)
    {
        if (!$request->isXmlHttpRequest()) {
            throw $this->createNotFoundException();
        }

        $status = 'fail';

        $originalContactDetails = $contact->getAdditionalDetails()->toArray();

        $form = $this->createForm('contact', $contact, array('allow_delete' => false));

        if ($request->isMethod('POST')) {
            $contact->getAdditionalDetails()->clear();
        }

        $form->handleRequest($request);

        if ($form->isValid()) {
            $this->removeContactDetails($contact, $originalContactDetails);

            $this->save($contact);

            $status = 'success';
        }

        return $this->json(
            array(
                'content' => $this->renderView(
                    'CSBillClientBundle:Ajax:contact_edit.html.twig',
                    array(
                        'form' => $form->createView(),
                        'contact' => $contact,
                    )
                ),
                'status' => $status,
            )
        );
    }

    /**
     * @param Contact                                               $contact
     * @param \CSBill\ClientBundle\Entity\AdditionalContactDetail[] $originalContactDetails
     */
    private function removeContactDetails(Contact $contact, array $originalContactDetails)
    {
        foreach ($contact->getAdditionalDetails() as $detail) {
            /* @var \CSBill\ClientBundle\Entity\ContactDetail $detail */
            foreach ($originalContactDetails as $key => $toDel) {
                if ($toDel->getId() === $detail->getId()) {
                    unset($originalContactDetails[$key]);
                }
            }
        }

        unset($detail);

        $em = $this->getEm();
        /** @var \CSBill\ClientBundle\Entity\AdditionalContactDetail $detail */
        foreach ($originalContactDetails as $detail) {
            $contact->removeAdditionalDetail($detail);
            $em->remove($detail);
        }
    }

    /**
     * Renders a contact card.
     *
     * @param Request $request
     * @param Contact $contact
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function contactAction(Request $request, Contact $contact)
    {
        if ($request->isMethod('DELETE')) {
            $client = $contact->getClient();

            if (count($client->getContacts()) === 1) {
                return $this->json(['message' => $this->trans('client.contact.at_least_1')], 500);
            }

            $entityManager = $this->getEm();
            $entityManager->remove($contact);
            $entityManager->flush();

            return $this->json([]);
        }

        return new JsonResponse($contact);
    }

    /**
     * Deletes a client.
     *
     * @param Client $client
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function deleteclientAction(Client $client)
    {
        $em = $this->getEm();
        $em->remove($client);
        $em->flush();

        $this->flash($this->trans('client_delete_success'), 'success');

        return $this->json(array('status' => 'success'));
    }
}
