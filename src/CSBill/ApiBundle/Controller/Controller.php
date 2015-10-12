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

use Doctrine\ORM\QueryBuilder;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcherInterface;
use Hateoas\Configuration\Route;
use Hateoas\Representation\Factory\PagerfantaFactory;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Exception\OutOfRangeCurrentPageException;
use Pagerfanta\Pagerfanta;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class Controller extends FOSRestController
{
    /**
     * @param Request                  $request
     * @param FormTypeInterface|string $form
     * @param mixed                    $entity
     * @param int                      $status
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function manageForm(Request $request, $form, $entity, $status = Response::HTTP_OK)
    {
        $form = $this->get('form.factory')->create($form, $entity);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $entityManager = $this->get('doctrine.orm.entity_manager');

            $entityManager->persist($entity);
            $entityManager->flush();

            return $this->handleView($this->view($entity, $status));
        }

        if (!$form->isSubmitted()) {
            $form->submit([]);
        }

        return $this->handleView($this->view($form, Response::HTTP_BAD_REQUEST));
    }

    /**
     * @param ParamFetcherInterface $fetcher
     * @param QueryBuilder          $queryBuilder
     * @param string                $route
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function manageCollection(ParamFetcherInterface $fetcher, QueryBuilder $queryBuilder, $route)
    {
        $limit = $fetcher->get('limit');
        $page = $fetcher->get('page');

        try {
            $pager = new Pagerfanta(new DoctrineORMAdapter($queryBuilder));
            $pager->setMaxPerPage($limit);
            $pager->setCurrentPage($page);
        } catch (OutOfRangeCurrentPageException $exception) {
            $response = ['message' => 'Page out of range'];

            return $this->handleView($this->view($response, Response::HTTP_NOT_FOUND));
        }

        $pagerFactory = new PagerfantaFactory();

        if (!$route instanceof Route) {
            $route = new Route($route, [], true, $this->get('router'));
        }

        $paginatedCollection = $pagerFactory->createRepresentation(
            $pager,
            $route
        );

        return $this->handleView($this->view($paginatedCollection));
    }

    /**
     * @param mixed $entity
     *
     * @return Response
     */
    protected function deleteEntity($entity)
    {
        $status = Response::HTTP_NO_CONTENT;

        try {
            $manager = $this->get('doctrine')->getManager();

            $manager->remove($entity);
            $manager->flush();
        } catch (\Exception $e) {
            $status = Response::HTTP_BAD_REQUEST;
        }

        return $this->handleView($this->view(null, $status));
    }
}
