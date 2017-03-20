<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\InvoiceBundle\Controller;

use CSBill\CoreBundle\Controller\BaseController;
use CSBill\InvoiceBundle\Entity\Invoice;
use CSBill\InvoiceBundle\Exception\InvalidTransitionException;
use CSBill\InvoiceBundle\Model\Graph;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class GridController extends BaseController
{
    /**
     * Archives a list of invoices.
     *
     * @param Request $request
     *
     * @return Response
     *
     * @throws InvalidTransitionException
     */
    public function archiveAction(Request $request): Response
    {
        $data = $request->request->get('data');

    /** @var Invoice[] $invoices */
    $invoices = $this->getRepository('CSBillInvoiceBundle:Invoice')->findBy(['id' => $data]);

        $invoiceManager = $this->get('invoice.manager');

        $em = $this->getEm();
        foreach ($invoices as $invoice) {
            if (!$this->get('finite.factory')->get($invoice, Graph::GRAPH)->can('archive')) {
                throw new InvalidTransitionException('archive');
            }

            $invoiceManager->archive($invoice);
        }

        $em->flush();

        return $this->json([]);
    }

    /**
     * Deletes a list of invoices.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function deleteAction(Request $request): Response
    {
        $data = $request->request->get('data');

    /* @var Invoice[] $invoice */
    $invoices = $this->getRepository('CSBillInvoiceBundle:Invoice')->findBy(['id' => $data]);

        $em = $this->getEm();
        foreach ($invoices as $invoice) {
            $em->remove($invoice);
        }

        $em->flush();

        return $this->json([]);
    }
}
