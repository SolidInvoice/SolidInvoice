<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\ApiBundle\Controller;

use CSBill\ClientBundle\Entity\Client;
use CSBill\ClientBundle\Model\Status;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcherInterface;
use Hateoas\Configuration\Route;
use Hateoas\Representation\Factory\PagerfantaFactory;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Exception\OutOfRangeCurrentPageException;
use Pagerfanta\Pagerfanta;
use Symfony\Component\HttpFoundation\Request;

class ClientController extends FOSRestController
{
    /**
     * @ApiDoc(
     *    statusCodes={
     *        200="Returned when successful",
     *        400="Returned when the page is out of range",
     *        403="Returned when the user is not authorized",
     *     },
     *     resource=true,
     *     description="Returns a list of all clients",
     * )
     *
     * @QueryParam(name="page", requirements="\d+", default="1", description="Current page of listing")
     * @QueryParam(name="limit", requirements="\d+", default="10", description="Number of results to return")
     *
     * @param ParamFetcherInterface $fetcher
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getClientsAction(ParamFetcherInterface $fetcher)
    {
        $limit = $fetcher->get('limit');
        $page = $fetcher->get('page');

        $clientRepository = $this->get('doctrine.orm.entity_manager')->getRepository('CSBillClientBundle:Client');
        $data = $clientRepository->createQueryBuilder('c');

        try {
            $pager = new Pagerfanta(new DoctrineORMAdapter($data));
            $pager->setMaxPerPage($limit);
            $pager->setCurrentPage($page);
        } catch (OutOfRangeCurrentPageException $exception) {
            $response = array('message' => 'Page out of range');

            return $this->handleView($this->view($response, 400));
        }

        $pagerFactory = new PagerfantaFactory();

        $paginatedCollection = $pagerFactory->createRepresentation(
            $pager,
            new Route('get_clients', array(), true, $this->get('router'))
        );

        return $this->handleView($this->view($paginatedCollection));
    }

    /**
     * @ApiDoc(
     *     statusCodes={
     *         200="Returned when successful",
     *         400="Returned when the validation fails",
     *         403="Returned when the user is not authorized",
     *     },
     *     resource=true,
     *     description="Create a new client",
     *     input="CSBill\ClientBundle\Form\Client",
     *     output="CSBill\ClientBundle\Entity\Client",
     * )
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function postClientsAction(Request $request)
    {
        $client = new Client();

        $form = $this->get('form.factory')->create(new \CSBill\ClientBundle\Form\Client(), $client);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $entityManager = $this->get('doctrine.orm.entity_manager');

            $client->setStatus(Status::STATUS_ACTIVE);

            $entityManager->persist($client);
            $entityManager->flush();

            return $this->handleView($this->view($client));
        }

        if (!$form->isSubmitted()) {
            $form->submit([]);
        }

        return $this->handleView($this->view($form));
    }

    /**
     * @ApiDoc(
     *      statusCodes={
     *         200="Returned when successful",
     *         403="Returned when the user is not authorized",
     *     },
     *      resource=true,
     *      description="Returns a list of all contacts for a specific client"
     * )
     * @QueryParam(name="page", requirements="\d+", default="1", description="Current page of listing")
     * @QueryParam(name="limit", requirements="\d+", default="10", description="Number of results to return")
     *
     * @param ParamFetcherInterface $fetcher
     * @param int                   $clientId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getClientContactsAction(ParamFetcherInterface $fetcher, $clientId)
    {
        $limit = $fetcher->get('limit');
        $page = $fetcher->get('page');

        $clientRepository = $this->get('doctrine.orm.entity_manager')->getRepository('CSBillClientBundle:Contact');
        $data = $clientRepository->createQueryBuilder('c');
        $data->where('c.client = :client')
            ->setParameter('client', $clientId);

        $pagerfanta = new Pagerfanta(new DoctrineORMAdapter($data));
        $pagerfanta->setMaxPerPage($limit);
        $pagerfanta->setCurrentPage($page);

        $pagerfantaFactory = new PagerfantaFactory();

        $paginatedCollection = $pagerfantaFactory->createRepresentation(
            $pagerfanta,
            new Route('get_client_contacts', array('clientId' => $clientId), true, $this->get('router'))
        );

        return $this->handleView($this->view($paginatedCollection));
    }
}
