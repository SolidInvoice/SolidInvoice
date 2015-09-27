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
use FOS\RestBundle\Controller\Annotations as RestRoute;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Request\ParamFetcherInterface;
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
        return $this->manageForm($request, 'invoice', new Entity\Invoice(), 201);
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
     * @RestRoute\Put(path="/invoices/{invoiceId}")
     *
     * @ParamConverter("invoice", class="CSBillInvoiceBundle:Invoice", options={"id" : "invoiceId"})
     *
     * @return Response
     */
    public function updateInvoiceAction(Request $request, Entity\Invoice $invoice)
    {
        return $this->manageForm($request, 'invoice', $invoice);
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
}
