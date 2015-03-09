<?php
/**
 * This file is part of the CSBill project.
 * 
 * @author      MiWay Development Team
 * @copyright   Copyright (c) 2014 MiWay Insurance Ltd
 */

namespace CSBill\ApiBundle\Controller;

use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcherInterface;
use Hateoas\Configuration\Route;
use Hateoas\Representation\Factory\PagerfantaFactory;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;

class ClientController extends FOSRestController
{
    /**
     * @ApiDoc(
     *      statusCodes={
     *         200="Returned when successful",
     *         403="Returned when the user is not authorized",
     *     },
     *      resource=true,
     *      description="Returns a list of all clients"
     * )
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

        $pagerfanta = new Pagerfanta(new DoctrineORMAdapter($data));
        $pagerfanta->setMaxPerPage($limit);
        $pagerfanta->setCurrentPage($page);

        $pagerfantaFactory   = new PagerfantaFactory();

        $paginatedCollection = $pagerfantaFactory->createRepresentation(
            $pagerfanta,
            new Route('get_clients', array(), true, $this->get('router'))
        );

        return $this->handleView($this->view($paginatedCollection));
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

        $pagerfantaFactory   = new PagerfantaFactory();

        $paginatedCollection = $pagerfantaFactory->createRepresentation(
            $pagerfanta,
            new Route('get_client_contacts', array('clientId' => $clientId), true, $this->get('router'))
        );

        return $this->handleView($this->view($paginatedCollection));
    }
}