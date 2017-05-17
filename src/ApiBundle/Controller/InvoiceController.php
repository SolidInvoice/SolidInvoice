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

namespace CSBill\ApiBundle\Controller;

use CSBill\InvoiceBundle\Entity;
use CSBill\InvoiceBundle\Form\Type\InvoiceType;
use CSBill\InvoiceBundle\Repository\InvoiceRepository;
use CSBill\PaymentBundle\Repository\PaymentRepository;
use FOS\RestBundle\Controller\Annotations as Rest;
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
     * @Rest\Get(path="/invoices")
     *
     * @param ParamFetcherInterface $fetcher
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getInvoicesAction(ParamFetcherInterface $fetcher): Response
    {
        /* @var InvoiceRepository $repository */
        $repository = $this->get('doctrine')
            ->getRepository('CSBillInvoiceBundle:Invoice');

        return $this->manageCollection($fetcher, $repository->createQueryBuilder('c'), 'get_invoices');
    }

    /**
     * @ApiDoc(
     *    statusCodes={
     *        200="Returned when successful",
     *        403="Returned when the user is not authorized",
     *        404="Returned when the invoice does not exist",
     *     },
     *     resource=true,
     *     description="Returns an Invoice",
     *     output={
     *         "class"="CSBill\InvoiceBundle\Entity\Invoice",
     *         "groups"={"api"}
     *     },
     *     authentication=true
     * )
     *
     * @Rest\Get(path="/invoice/{invoiceId}")
     *
     * @param Entity\Invoice $invoice
     *
     * @ParamConverter("invoice", class="CSBillInvoiceBundle:Invoice", options={"id" : "invoiceId"})
     *
     * @return Response
     *
     * @throws \Exception
     */
    public function getInvoiceAction(Entity\Invoice $invoice): Response
    {
        return $this->handleView($this->view($invoice));
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
     * @Rest\Post(path="/invoices")
     *
     * @return Response
     */
    public function createInvoiceAction(Request $request): Response
    {
        $invoice = new Entity\Invoice();

        $form = $this->get('form.factory')->create('invoice', $invoice);

        $form->submit($request->request->all());

        if ($form->isValid()) {
            $this->get('invoice.manager')->create($invoice);

            return $this->handleView($this->view($invoice, Response::HTTP_CREATED));
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
     * @Rest\Patch(path="/invoice/{invoiceId}")
     *
     * @ParamConverter("invoice", class="CSBillInvoiceBundle:Invoice", options={"id" : "invoiceId"})
     */
    public function updateInvoiceAction(Request $request, Entity\Invoice $invoice): Response
    {
        $originalStatus = $invoice->getStatus();
        $form = $this->get('form.factory')->create(InvoiceType::class, $invoice);

        $form->handleRequest($request);

        if ($form->isValid()) {
            if ($invoice->getStatus() !== $originalStatus) {
                throw new \Exception('To change the status of an invoice, use the dedicated "status" method', 400);
            }

            $entityManager = $this->get('doctrine')->getManager();

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
     * @Rest\Patch(path="/invoice/{invoiceId}/status")
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

        $transitions = $this->get('state_machine.invoice')->getEnabledTransitions($invoice);

        if (!in_array($status, $transitions, true)) {
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
     * @Rest\Delete(path="/invoice/{invoiceId}")
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
     * @Rest\Get(path="/invoice/{invoiceId}/payments")
     *
     * @param ParamFetcherInterface $fetcher
     * @param int                   $invoiceId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getInvoicePaymentsAction(ParamFetcherInterface $fetcher, $invoiceId)
    {
        /* @var PaymentRepository $repository */
        $repository = $this->get('doctrine')
            ->getRepository('CSBillPaymentBundle:Payment');

        $queryBuilder = $repository->createQueryBuilder('i')
            ->where('i.invoice = :invoice')
            ->setParameter('invoice', $invoiceId);

        $route = new Route('get_invoice_payments', ['invoiceId' => $invoiceId], true, $this->get('router'));

        return $this->manageCollection($fetcher, $queryBuilder, $route);
    }
}
