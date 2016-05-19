<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\ApiBundle\Controller;

use CSBill\ClientBundle\Entity;
use CSBill\ClientBundle\Form\Client;
use CSBill\ClientBundle\Form\Contact;
use FOS\RestBundle\Controller\Annotations as RestRoute;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Request\ParamFetcherInterface;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ClientController extends Controller
{
    /**
     * @ApiDoc(
     *    statusCodes={
     *        200="Returned when successful",
     *        403="Returned when the user is not authorized",
     *        404="Returned when the page is out of range",
     *     },
     *     resource=true,
     *     description="Returns a list of all clients",
     *     authentication=true,
     * )
     *
     * @QueryParam(name="page", requirements="\d+", default="1", description="Current page of listing")
     * @QueryParam(name="limit", requirements="\d+", default="10", description="Number of results to return")
     *
     * @RestRoute\Get(path="/clients")
     *
     * @param ParamFetcherInterface $fetcher
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getClientsAction(ParamFetcherInterface $fetcher)
    {
        $clientRepository = $this->get('doctrine.orm.entity_manager')
            ->getRepository('CSBillClientBundle:Client');

        return $this->manageCollection($fetcher, $clientRepository->createQueryBuilder('c'), 'get_clients');
    }

    /**
     * @ApiDoc(
     *     statusCodes={
     *         200="Returned when successful",
     *         403="Returned when the user is not authorized",
     *         404="Returned when the page is out of range",
     *     },
     *     resource=true,
     *     description="Returns a list of all contacts for a specific client",
     *     authentication=true,
     * )
     *
     * @QueryParam(name="page", requirements="\d+", default="1", description="Current page of listing")
     * @QueryParam(name="limit", requirements="\d+", default="10", description="Number of results to return")
     *
     * @param ParamFetcherInterface $fetcher
     * @param int                   $clientId
     *
     * @RestRoute\Get(path="/clients/{clientId}/contacts")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getClientContactsAction(ParamFetcherInterface $fetcher, $clientId)
    {
        $clientRepository = $this->get('doctrine.orm.entity_manager')
            ->getRepository('CSBillClientBundle:Contact');

        $data = $clientRepository->createQueryBuilder('c');
        $data->where('c.client = :client')
            ->setParameter('client', $clientId);

        return $this->manageCollection($fetcher, $data, 'get_clients');
    }

    /**
     * @ApiDoc(
     *     statusCodes={
     *         201="Returned when successful",
     *         400="Returned when the validation fails",
     *         403="Returned when the user is not authorized",
     *     },
     *     resource=true,
     *     description="Create a new client",
     *     input="CSBill\ClientBundle\Form\Client",
     *     output="CSBill\ClientBundle\Entity\Client",
     *     authentication=true,
     * )
     *
     * @param Request $request
     *
     * @RestRoute\Post(path="/clients")
     *
     * @return Response
     */
    public function createClientAction(Request $request)
    {
        return $this->manageForm($request, new Client(), new Entity\Client(), Response::HTTP_CREATED);
    }

    /**
     * @ApiDoc(
     *     statusCodes={
     *         200="Returned when successful",
     *         400="Returned when the validation fails",
     *         403="Returned when the user is not authorized",
     *     },
     *     resource=true,
     *     description="Update a client",
     *     input="CSBill\ClientBundle\Form\Client",
     *     output="CSBill\ClientBundle\Entity\Client",
     *     authentication=true,
     * )
     *
     * @param Request       $request
     * @param Entity\Client $client
     *
     * @RestRoute\Put(path="/clients/{clientId}")
     *
     * @ParamConverter("client", class="CSBillClientBundle:Client", options={"id" : "clientId"})
     *
     * @return Response
     */
    public function updateClientAction(Request $request, Entity\Client $client)
    {
        return $this->manageForm($request, new Client(), $client);
    }

    /**
     * @ApiDoc(
     *     statusCodes={
     *         204="Returned when successful",
     *         403="Returned when the user is not authorized",
     *     },
     *     resource=true,
     *     description="Delete a client",
     *     authentication=true,
     * )
     *
     * @param Entity\Client $client
     *
     * @RestRoute\Delete(path="/clients/{clientId}")
     *
     * @ParamConverter("client", class="CSBillClientBundle:Client", options={"id" : "clientId"})
     *
     * @return Response
     */
    public function deleteClientAction(Entity\Client $client)
    {
        return $this->deleteEntity($client);
    }

    /**
     * @ApiDoc(
     *     statusCodes={
     *         201="Returned when successful",
     *         400="Returned when the validation fails",
     *         403="Returned when the user is not authorized",
     *     },
     *     resource=true,
     *     description="Create a new contact",
     *     input="CSBill\ClientBundle\Form\Contact",
     *     output="CSBill\ClientBundle\Entity\Contact",
     *     authentication=true,
     * )
     *
     * @param Request       $request
     * @param Entity\Client $client
     *
     * @ParamConverter("client", class="CSBillClientBundle:Client", options={"id" : "clientId"})
     *
     * @RestRoute\Post(path="/clients/{clientId}/contacts")
     *
     * @return Response
     */
    public function createContactAction(Request $request, Entity\Client $client)
    {
        $contact = new Entity\Contact();
        $contact->setClient($client);

        return $this->manageForm($request, 'contact', $contact, Response::HTTP_CREATED);
    }

    /**
     * @ApiDoc(
     *     statusCodes={
     *         200="Returned when successful",
     *         400="Returned when the validation fails",
     *         403="Returned when the user is not authorized",
     *     },
     *     resource=true,
     *     description="Update a contact",
     *     input="CSBill\ClientBundle\Form\Contact",
     *     output="CSBill\ClientBundle\Entity\Contact",
     *     authentication=true,
     * )
     *
     * @param Request        $request
     * @param Entity\Client  $client
     * @param Entity\Contact $contact
     *
     * @ParamConverter("client", class="CSBillClientBundle:Client", options={"id" : "clientId"})
     * @ParamConverter("contact", class="CSBillClientBundle:Contact", options={"id" : "contactId"})
     *
     * @RestRoute\Put(path="/clients/{clientId}/contacts/{contactId}")
     *
     * @return Response
     */
    public function updateContactAction(Request $request, Entity\Client $client, Entity\Contact $contact)
    {
        $contact->setClient($client);

        return $this->manageForm($request, 'contact', $contact);
    }

    /**
     * @ApiDoc(
     *     statusCodes={
     *         204="Returned when successful",
     *         403="Returned when the user is not authorized",
     *         404="Returned when the contact does not exist on the client",
     *     },
     *     resource=true,
     *     description="Delete a contact",
     *     authentication=true,
     * )
     *
     * @param Entity\Client  $client
     * @param Entity\Contact $contact
     *
     * @RestRoute\Delete(path="/clients/{clientId}/contacts/{contactId}")
     *
     * @ParamConverter("client", class="CSBillClientBundle:Client", options={"id" : "clientId"})
     * @ParamConverter("contact", class="CSBillClientBundle:Contact", options={"id" : "contactId"})
     *
     * @return Response
     */
    public function deleteContactAction(Entity\Client $client, Entity\Contact $contact)
    {
        if ($client->getContacts()->contains($contact)) {
            throw $this->createNotFoundException();
        }

        return $this->deleteEntity($contact);
    }
}
