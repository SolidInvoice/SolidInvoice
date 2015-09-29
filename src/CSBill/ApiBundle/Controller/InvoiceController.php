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

use CSBill\InvoiceBundle\Entity;
use CSBill\InvoiceBundle\Model\Graph;
use FOS\RestBundle\Controller\Annotations as RestRoute;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Request\ParamFetcherInterface;
use Hateoas\Configuration\Route;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class InvoiceController extends Controller
{
    /**
     * @ApiDoc(
     *    statusCodes={
     *        200="Returned when successful",
     *        403="Returned when the user is not authorized",
     *        404="Returned when the page is out of range",
     *     },
     *     resource=true,
     *     description="Returns a list of all invoices",
     *     authentication=true,
     * )
     *
     * @QueryParam(name="page", requirements="\d+", default="1", description="Current page of listing")
     * @QueryParam(name="limit", requirements="\d+", default="10", description="Number of results to return")
     *
     * @RestRoute\Get(path="/invoices")
     *
     * @param ParamFetcherInterface $fetcher
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getInvoicesAction(ParamFetcherInterface $fetcher)
    {
        $repository = $this->get('doctrine.orm.entity_manager')
            ->getRepository('CSBillInvoiceBundle:Invoice');

        return $this->manageCollection($fetcher, $repository->createQueryBuilder('c'), 'get_invoices');
    }

    /**
     * @ApiDoc(
     *     statusCodes={
     *         201="Returned when successful",
     *         400="Returned when the validation fails",
     *         403="Returned when the user is not authorized",
     *     },
     *     resource=true,
     *     description="Create a new invoice",
     *     input="CSBill\InvoiceBundle\Form\Type\InvoiceType",
     *     output="CSBill\InvoiceBundle\Entity\Invoice",
     *     authentication=true,
     * )
     *
     * @param Request $request
     *
     * @RestRoute\Post(path="/invoices")
     *
     * @return Response
     */
    public function createInvoiceAction(Request $request)
    {
        $invoice = new Entity\Invoice();

        $form = $this->get('form.factory')->create('invoice', $invoice);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $this->get('invoice.manager')->create($invoice);

            return $this->handleView($this->view($invoice, Response::HTTP_CREATED));
        }

        if (!$form->isSubmitted()) {
            $form->submit([]);
        }

        return $this->handleView($this->view($form));
    }

    /**
     * @ApiDoc(
     *     statusCodes={
     *         200="Returned when successful",
     *         400="Returned when the validation fails",
     *         403="Returned when the user is not authorized",
     *     },
     *     resource=true,
     *     description="Update an invoice",
     *     input="CSBill\InvoiceBundle\Form\Type\InvoiceType",
     *     output="CSBill\InvoiceBundle\Entity\Invoice",
     *     authentication=true,
     * )
     *
     * @param Request        $request
     * @param Entity\Invoice $invoice
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Exception
     *
     * @RestRoute\Put(path="/invoices/{invoiceId}")
     *
     * @ParamConverter("invoice", class="CSBillInvoiceBundle:Invoice", options={"id" : "invoiceId"})
     */
    public function updateInvoiceAction(Request $request, Entity\Invoice $invoice)
    {
        $originalStatus = $invoice->getStatus();
        $form = $this->get('form.factory')->create('invoice', $invoice);

        $form->handleRequest($request);

        if ($form->isValid()) {
            if ($invoice->getStatus() !== $originalStatus) {
                throw new \Exception('To change the status of an invoice, use the dedicated "status" method', 400);
            }

            $entityManager = $this->get('doctrine.orm.entity_manager');

            $entityManager->persist($invoice);
            $entityManager->flush();

            return $this->handleView($this->view($invoice));
        }

        if (!$form->isSubmitted()) {
            $form->submit([]);
        }

        return $this->handleView($this->view($form, Response::HTTP_BAD_REQUEST));
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
     *     description="Update the status of an Invoice",
     *     authentication=true,
     * )
     *
     * @param Request        $request
     * @param Entity\Invoice $invoice
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Exception
     *
     * @RestRoute\Patch(path="/invoices/{invoiceId}/status")
     *
     * @ParamConverter("invoice", class="CSBillInvoiceBundle:Invoice", options={"id" : "invoiceId"})
     */
    public function updateInvoiceStatusAction(Request $request, Entity\Invoice $invoice)
    {
        if (!$request->request->has('status')) {
            throw new \Exception('You need to provide a status', Response::HTTP_BAD_REQUEST);
        }

        $status = $request->request->get('status');
        $manager = $this->get('invoice.manager');

        $transitions = $this->get('finite.factory')->get($invoice, Graph::GRAPH)->getTransitions();

        if (!in_array($status, $transitions)) {
            throw new \Exception(sprintf('The value "%s" is not valid', $status), Response::HTTP_BAD_REQUEST);
        }

        $manager->{$status}($invoice);

        return $this->handleView($this->view($invoice));
    }

    /**
     * @ApiDoc(
     *     statusCodes={
     *         204="Returned when successful",
     *         403="Returned when the user is not authorized",
     *     },
     *     resource=true,
     *     description="Delete an invoice",
     *     authentication=true,
     * )
     *
     * @param Entity\Invoice $invoice
     *
     * @RestRoute\Delete(path="/invoices/{invoiceId}")
     *
     * @ParamConverter("invoice", class="CSBillInvoiceBundle:Invoice", options={"id" : "invoiceId"})
     *
     * @return Response
     */
    public function deleteInvoiceAction(Entity\Invoice $invoice)
    {
        return $this->deleteEntity($invoice);
    }

    /**
     * @ApiDoc(
     *    statusCodes={
     *        200="Returned when successful",
     *        403="Returned when the user is not authorized",
     *        404="Returned when the page is out of range",
     *     },
     *     resource=true,
     *     description="Returns a list of all payments for an invoice",
     *     authentication=true,
     * )
     *
     * @QueryParam(name="page", requirements="\d+", default="1", description="Current page of listing")
     * @QueryParam(name="limit", requirements="\d+", default="10", description="Number of results to return")
     *
     * @RestRoute\Get(path="/invoices/{invoiceId}/payments")
     *
     * @param ParamFetcherInterface $fetcher
     * @param int                   $invoiceId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getInvoicePaymentsAction(ParamFetcherInterface $fetcher, $invoiceId)
    {
        $repository = $this->get('doctrine.orm.entity_manager')
            ->getRepository('CSBillPaymentBundle:Payment');

        $queryBuilder = $repository->createQueryBuilder('i')
            ->where('i.invoice = :invoice')
            ->setParameter('invoice', $invoiceId);

        $route = new Route('get_invoice_payments', ['invoiceId' => $invoiceId], true, $this->get('router'));

        return $this->manageCollection($fetcher, $queryBuilder, $route);
    }
}
