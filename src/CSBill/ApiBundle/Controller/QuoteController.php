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

use CSBill\QuoteBundle\Entity;
use CSBill\QuoteBundle\Model\Graph;
use FOS\RestBundle\Controller\Annotations as RestRoute;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Request\ParamFetcherInterface;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class QuoteController extends Controller
{
    /**
     * @ApiDoc(
     *    statusCodes={
     *        200="Returned when successful",
     *        403="Returned when the user is not authorized",
     *        404="Returned when the page is out of range",
     *     },
     *     resource=true,
     *     description="Returns a list of all quotes",
     *     authentication=true,
     * )
     *
     * @QueryParam(name="page", requirements="\d+", default="1", description="Current page of listing")
     * @QueryParam(name="limit", requirements="\d+", default="10", description="Number of results to return")
     *
     * @RestRoute\Get(path="/quotes")
     *
     * @param ParamFetcherInterface $fetcher
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getQuotesAction(ParamFetcherInterface $fetcher)
    {
        $repository = $this->get('doctrine.orm.entity_manager')
            ->getRepository('CSBillQuoteBundle:Quote');

        return $this->manageCollection($fetcher, $repository->createQueryBuilder('c'), 'get_quotes');
    }

    /**
     * @ApiDoc(
     *     statusCodes={
     *         201="Returned when successful",
     *         400="Returned when the validation fails",
     *         403="Returned when the user is not authorized",
     *     },
     *     resource=true,
     *     description="Create a new quote",
     *     input="CSBill\QuoteBundle\Form\Type\QuoteType",
     *     output="CSBill\QuoteBundle\Entity\Quote",
     *     authentication=true,
     * )
     *
     * @param Request $request
     *
     * @RestRoute\Post(path="/quotes")
     *
     * @return Response
     */
    public function createQuoteAction(Request $request)
    {
        return $this->manageForm($request, 'quote', new Entity\Quote(), Response::HTTP_CREATED);
    }

    /**
     * @ApiDoc(
     *     statusCodes={
     *         200="Returned when successful",
     *         400="Returned when the validation fails",
     *         403="Returned when the user is not authorized",
     *     },
     *     resource=true,
     *     description="Update an quote",
     *     input="CSBill\QuoteBundle\Form\Type\QuoteType",
     *     output="CSBill\QuoteBundle\Entity\Quote",
     *     authentication=true,
     * )
     *
     * @param Request      $request
     * @param Entity\Quote $quote
     *
     * @RestRoute\Put(path="/quotes/{quoteId}")
     *
     * @ParamConverter("quote", class="CSBillQuoteBundle:Quote", options={"id" : "quoteId"})
     *
     * @return Response
     */
    public function updateQuoteAction(Request $request, Entity\Quote $quote)
    {
        return $this->manageForm($request, 'quote', $quote);
    }

    /**
     * @ApiDoc(
     *     statusCodes={
     *         200="Returned when successful",
     *         400="Returned when the validation fails",
     *         403="Returned when the user is not authorized",
     *     },
     *     parameters={{"name" : "status", "dataType" : "string", "required" : true}},
     *     resource=true,
     *     description="Update the status of a Quote",
     *     authentication=true,
     * )
     *
     * @param Request      $request
     * @param Entity\Quote $quote
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Exception
     *
     * @RestRoute\Patch(path="/quotes/{quoteId}/status")
     *
     * @ParamConverter("quote", class="CSBillQuoteBundle:Quote", options={"id" : "quoteId"})
     */
    public function updateQuoteStatusAction(Request $request, Entity\Quote $quote)
    {
        if (!$request->request->has('status')) {
            throw new \Exception('You need to provide a status', Response::HTTP_BAD_REQUEST);
        }

        $status = $request->request->get('status');
        $manager = $this->get('quote.manager');

        $transitions = $this->get('finite.factory')->get($quote, Graph::GRAPH)->getTransitions();

        if (!in_array($status, $transitions)) {
            throw new \Exception(sprintf('The value "%s" is not valid', $status), Response::HTTP_BAD_REQUEST);
        }

        $manager->{$status}($quote);

        return $this->handleView($this->view($quote));
    }

    /**
     * @ApiDoc(
     *     statusCodes={
     *         204="Returned when successful",
     *         403="Returned when the user is not authorized",
     *     },
     *     resource=true,
     *     description="Delete an quote",
     *     authentication=true,
     * )
     *
     * @param Entity\Quote $quote
     *
     * @RestRoute\Delete(path="/quotes/{quoteId}")
     *
     * @ParamConverter("quote", class="CSBillQuoteBundle:Quote", options={"id" : "quoteId"})
     *
     * @return Response
     */
    public function deleteQuoteAction(Entity\Quote $quote)
    {
        return $this->deleteEntity($quote);
    }
}
