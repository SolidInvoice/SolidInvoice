<?php

namespace CSBill\CoreBundle\Controller;

use CSBill\CoreBundle\Entity\Tax;
use CSBill\CoreBundle\Form\Type\TaxType;
use CSBill\CoreBundle\Grid\TaxGrid;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class TaxController extends BaseController
{
    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     */
    public function ratesAction()
    {
        $grid = $this->get('grid')->create(new TaxGrid);

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

        return $this->render('CSBillCoreBundle:Tax:add.html.twig', array('form' => $form->createView()));
    }

    /**
     * @param Tax $tax
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction(Tax $tax)
    {
        $entityMnager = $this->getEm();

        $this->getRepository('CSBillInvoiceBundle:Item')->removeTax($tax);
        $this->getRepository('CSBillQuoteBundle:Item')->removeTax($tax);
        $entityMnager->remove($tax);
        $entityMnager->flush();

        $this->flash($this->trans('Tax Deleted'), 'success');

        return new JsonResponse(array("status" => "success"));
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
