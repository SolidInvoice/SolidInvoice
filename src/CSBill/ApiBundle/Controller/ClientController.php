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
use CSBill\ClientBundle\Model\Status;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Request\ParamFetcherInterface;
use Hateoas\Configuration\Route;
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
     * @Rest\Get(path="/clients")
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
     *    statusCodes={
     *        200="Returned when successful",
     *        403="Returned when the user is not authorized",
     *        404="Returned when the client does not exist",
     *     },
     *     resource=true,
     *     description="Returns a client",
     *     output={
     *         "class"="CSBill\ClientBundle\Entity\Client",
     *         "groups"={"api"}
     *     },
     *     authentication=true
     * )
     *
     * @Rest\Get(path="/client/{clientId}")
     *
     * @param int $clientId
     *
     * @return Response
     *
     * @throws \Exception
     */
    public function getClientAction($clientId)
    {
        $clientRepository = $this->get('doctrine.orm.entity_manager')
            ->getRepository('CSBillClientBundle:Client');

        $client = $clientRepository->find($clientId);

        if (null === $client) {
            throw new \Exception(sprintf('Client %d does not exist', $clientId));
        }

        return $this->handleView($this->view($client));
    }

    /**
     * @ApiDoc(
     *     statusCodes={
     *         200="Returned when successful",
     *         403="Returned when the user is not authorized",
     *         404="Returned when the client or contact does not exist",
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
     * @Rest\Get(path="/client/{clientId}/contacts")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getClientContactsAction(ParamFetcherInterface $fetcher, $clientId)
    {
        $contactRepository = $this->get('doctrine.orm.entity_manager')
            ->getRepository('CSBillClientBundle:Contact');

        $data = $contactRepository->createQueryBuilder('c');
        $data->where('c.client = :client')
            ->setParameter('client', $clientId);

        return $this->manageCollection(
            $fetcher,
            $data,
            new Route('get_client_contacts', ['clientId' => $clientId], true, $this->get('router'))
        );
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
     *     output={
     *         "class"="CSBill\ClientBundle\Entity\Contact",
     *         "groups"={"api"}
     *     },
     * )
     *
     * @param Entity\Client  $client
     * @param Entity\Contact $contact
     *
     * @ParamConverter("client", class="CSBillClientBundle:Client", options={"id" : "clientId"})
     * @ParamConverter("contact", class="CSBillClientBundle:Contact", options={"id" : "contactId"})
     *
     * @return Response
     *
     * @Rest\Get(path="/client/{clientId}/contact/{contactId}")
     */
    public function getClientContactAction(Entity\Client $client, Entity\Contact $contact)
    {
        if (!$client->getContacts()->contains($contact)) {
            throw $this->createNotFoundException();
        }

        return $this->handleView($this->view($contact));
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
     *     output={
     *         "class"="CSBill\ClientBundle\Entity\Client",
     *         "groups"={"api"}
     *     },
     *     authentication=true,
     * )
     *
     * @param Request $request
     *
     * @Rest\Post(path="/clients")
     *
     * @return Response
     */
    public function createClientAction(Request $request)
    {
        $entity = new Entity\Client();
        $entity->setStatus(Status::STATUS_ACTIVE);

        return $this->manageForm($request, new Client(), $entity, Response::HTTP_CREATED);
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
     *     output={
     *         "class"="CSBill\ClientBundle\Entity\Client",
     *         "groups"={"api"}
     *     },
     *     authentication=true,
     * )
     *
     * @param Request       $request
     * @param Entity\Client $client
     *
     * @Rest\Patch(path="/client/{clientId}")
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
     * @Rest\Delete(path="/client/{clientId}")
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
     *     output={
     *         "class"="CSBill\ClientBundle\Entity\Contact",
     *         "groups"={"api"}
     *     },
     *     authentication=true,
     * )
     *
     * @param Request       $request
     * @param Entity\Client $client
     *
     * @ParamConverter("client", class="CSBillClientBundle:Client", options={"id" : "clientId"})
     *
     * @Rest\Post(path="/client/{clientId}/contacts")
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
     *     output={
     *         "class"="CSBill\ClientBundle\Entity\Contact",
     *         "groups"={"api"}
     *     },
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
     * @Rest\Patch(path="/client/{clientId}/contact/{contactId}")
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
     * @Rest\Delete(path="/client/{clientId}/contact/{contactId}")
     *
     * @ParamConverter("client", class="CSBillClientBundle:Client", options={"id" : "clientId"})
     * @ParamConverter("contact", class="CSBillClientBundle:Contact", options={"id" : "contactId"})
     *
     * @return Response
     */
    public function deleteContactAction(Entity\Client $client, Entity\Contact $contact)
    {
        if (!$client->getContacts()->contains($contact)) {
            throw $this->createNotFoundException();
        }

        return $this->deleteEntity($contact);
    }
}
