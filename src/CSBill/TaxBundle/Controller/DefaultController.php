<?php

/*
 * This file is part of CSBill package.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\TaxBundle\Controller;

use CSBill\CoreBundle\Controller\BaseController;
use CSBill\TaxBundle\Entity\Tax;
use CSBill\TaxBundle\Form\Type\TaxType;
use CSBill\TaxBundle\Grid\TaxGrid;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends BaseController
{
    /**
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Exception
     */
    public function ratesAction()
    {
        $grid = $this->get('grid')->create(new TaxGrid());

        return $grid->getGridResponse();
    }

    /**
     * @param Request $request
     * @param Tax     $tax
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function addAction(Request $request, Tax $tax = null)
    {
        $tax = $tax ?: new Tax();

        $form = $this->createForm(new TaxType(), $tax);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $this->save($tax);

            $this->flash($this->trans('Tax rate successfully saved'), 'success');

            return $this->redirect($this->generateUrl('_tax_rates'));
        }

        return $this->render('CSBillTaxBundle:Default:add.html.twig', array('form' => $form->createView()));
    }

    /**
     * @param Tax $tax
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction(Tax $tax)
    {
        $entityMnager = $this->getEm();

        /** @var \CSBill\InvoiceBundle\Repository\ItemRepository $invoiceRepository */
        $invoiceRepository = $this->getRepository('CSBillInvoiceBundle:Item');
        $invoiceRepository->removeTax($tax);

        /** @var \CSBill\QuoteBundle\Repository\ItemRepository $quoteRepository */
        $quoteRepository = $this->getRepository('CSBillQuoteBundle:Item');
        $quoteRepository->removeTax($tax);

        $entityMnager->remove($tax);
        $entityMnager->flush();

        $this->flash($this->trans('Tax Deleted'), 'success');

        return new JsonResponse(array('status' => 'success'));
    }

    /**
     * @param Tax $tax
     *
     * @return JsonResponse
     */
    public function getAction(Tax $tax)
    {
        $result = array(
            'id' => $tax->getId(),
            'name' => $tax->getName(),
            'type' => $tax->getType(),
            'rate' => $tax->getRate(),
        );

        return new JsonResponse($result);
    }
}
