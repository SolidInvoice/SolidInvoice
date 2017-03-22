<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\ClientBundle\Controller;

use CSBill\ClientBundle\Entity\Address;
use CSBill\ClientBundle\Entity\Client;
use CSBill\ClientBundle\Entity\Contact;
use CSBill\ClientBundle\Form\Type\AddressType;
use CSBill\ClientBundle\Form\Type\ContactType;
use CSBill\CoreBundle\Controller\BaseController;
use JMS\Serializer\SerializationContext;
use Money\Currency;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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
    public function infoAction(Client $client, string $type = 'quote'): Response
    {
        $content = $this->renderView(
            'CSBillClientBundle:Ajax:info.html.twig',
            [
                'client' => $client,
                'type' => $type,
            ]
        );

        $currency = $client->getCurrency() ?: new Currency($this->getParameter('currency'));

        return $this->json(
            [
                'content' => $content,
                'currency' => $currency->getCode(),
                'currency_format' => $this->get('csbill.money.formatter')->getCurrencySymbol($currency),
            ]
        );
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
    public function addcontactAction(Request $request, Client $client): Response
    {
        /*if (!$request->isXmlHttpRequest()) {
            throw $this->createNotFoundException();
        }*/

        $contact = new Contact();
        $contact->setClient($client);

        $form = $this->createForm(ContactType::class, $contact, ['allow_delete' => false]);

        $response = [];

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $this->save($contact);

                $response = [
                    'status' => 'success',
                    'contact' => $contact,
                ];

                return $this->serializeResponse($response);
            } else {
                $response['status'] = 'failure';
            }
        }

        $content = $this->renderView(
            'CSBillClientBundle:Ajax:contact_add.html.twig',
            [
                'form' => $form->createView(),
                'client' => $client,
            ]
        );

        $response['content'] = $content;

        return $this->json($response);
    }

    /**
     * @param mixed $response
     *
     * @return Response
     */
    private function serializeResponse($response)
    {
        $context = SerializationContext::create()->setGroups(['js']);

        return new Response($this->get('serializer')->serialize($response, 'json', $context), 200, ['Content-Type' => 'application/json']);
    }

    /**
     * @param Request $request
     * @param Address $address
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|Response
     */
    public function addressAction(Request $request, Address $address)
    {
        if ($request->isMethod('DELETE')) {
            $entityManager = $this->getEm();
            $entityManager->remove($address);
            $entityManager->flush();

            return $this->json([]);
        }

        return $this->serializeResponse($address);
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

        $status = 'success';

        $originalContactDetails = $contact->getAdditionalDetails()->toArray();

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
        );
    }

    /**
     * @param Contact                                               $contact
     * @param \CSBill\ClientBundle\Entity\AdditionalContactDetail[] $originalContactDetails
     */
    private function removeContactDetails(Contact $contact, array $originalContactDetails)
    {
        foreach ($contact->getAdditionalDetails() as $detail) {
            /* @var \CSBill\ClientBundle\Entity\AdditionalContactDetail $detail */
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
     * Edits a contact.
     *
     * @param Request $request
     * @param Address $address
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function editAddressAction(Request $request, Address $address)
    {
        if (!$request->isXmlHttpRequest()) {
            throw $this->createNotFoundException();
        }

        $status = 'success';

        $form = $this->createForm(AddressType::class, $address, ['canDelete' => false]);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $this->save($address);
        } elseif ($form->isSubmitted()) {
            $status = 'failure';
        }

        return $this->json(
            [
                'content' => $this->renderView(
                    'CSBillClientBundle:Ajax:address_edit.html.twig',
                    [
                        'form' => $form->createView(),
                        'address' => $address,
                    ]
                ),
                'status' => $status,
            ]
        );
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

        return $this->serializeResponse($contact);
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

        $this->flash($this->trans('client.delete_success'), 'success');

        return $this->json(['status' => 'success']);
    }
}
