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
use CSBill\QuoteBundle\Form\Type\QuoteType;
use CSBill\QuoteBundle\Model\Graph;
use CSBill\QuoteBundle\Repository\QuoteRepository;
use FOS\RestBundle\Controller\Annotations as Rest;
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
     * @Rest\Get(path="/quotes")
     *
     * @param ParamFetcherInterface $fetcher
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getQuotesAction(ParamFetcherInterface $fetcher)
    {
	/* @var QuoteRepository $repository */
	$repository = $this->get('doctrine')
	    ->getRepository('CSBillQuoteBundle:Quote');

	return $this->manageCollection($fetcher, $repository->createQueryBuilder('c'), 'get_quotes');
    }

    /**
     * @ApiDoc(
     *    statusCodes={
     *        200="Returned when successful",
     *        403="Returned when the user is not authorized",
     *        404="Returned when the quote does not exist",
     *     },
     *     resource=true,
     *     description="Returns a Quote",
     *     output={
     *         "class"="CSBill\QuoteBundle\Entity\Quote",
     *         "groups"={"api"}
     *     },
     *     authentication=true
     * )
     *
     * @Rest\Get(path="/quote/{quoteId}")
     *
     * @param Entity\Quote $quote
     *
     * @ParamConverter("quote", class="CSBillQuoteBundle:Quote", options={"id" : "quoteId"})
     *
     * @return Response
     *
     * @throws \Exception
     */
    public function getQuoteAction(Entity\Quote $quote)
    {
	return $this->handleView($this->view($quote));
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
     * @Rest\Post(path="/quotes")
     *
     * @return Response
     */
    public function createQuoteAction(Request $request)
    {
	$entity = new Entity\Quote();
	$entity->setStatus($request->request->get('status', Graph::STATUS_DRAFT));

	$request->request->remove('status');

	return $this->manageForm($request, QuoteType::class, $entity, Response::HTTP_CREATED);
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
     * @Rest\Patch(path="/quote/{quoteId}")
     *
     * @ParamConverter("quote", class="CSBillQuoteBundle:Quote", options={"id" : "quoteId"})
     *
     * @return Response
     */
    public function updateQuoteAction(Request $request, Entity\Quote $quote)
    {
	return $this->manageForm($request, QuoteType::class, $quote);
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
     * @Rest\Patch(path="/quote/{quoteId}/status")
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
     * @Rest\Delete(path="/quote/{quoteId}")
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
