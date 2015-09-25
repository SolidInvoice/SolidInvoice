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

abstract class Controller extends FOSRestController
{
    /**
     * @param Request                  $request
     * @param FormTypeInterface|string $form
     * @param mixed                    $entity
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function manageClientForm(Request $request, $form, $entity)
    {
        $form = $this->get('form.factory')->create($form, $entity);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $entityManager = $this->get('doctrine.orm.entity_manager');

            $entityManager->persist($entity);
            $entityManager->flush();

            return $this->handleView($this->view($entity));
        }

        if (!$form->isSubmitted()) {
            $form->submit([]);
        }

        return $this->handleView($this->view($form));
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

            return $this->handleView($this->view($response, 400));
        }

        $pagerFactory = new PagerfantaFactory();

        $paginatedCollection = $pagerFactory->createRepresentation(
            $pager,
            new Route($route, [], true, $this->get('router'))
        );

        return $this->handleView($this->view($paginatedCollection));
    }
}