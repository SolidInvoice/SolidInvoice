<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
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
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends BaseController
{
    /**
     * @return Response
     *
     * @throws \Exception
     */
    public function ratesAction(): Response
    {
        return $this->render('CSBillTaxBundle:Default:index.html.twig');
    }

    /**
     * @param Request $request
     * @param Tax     $tax
     *
     * @return Response
     */
    public function addAction(Request $request, Tax $tax = null): Response
    {
        $tax = $tax ?: new Tax();

        $form = $this->createForm(TaxType::class, $tax);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $this->save($tax);

            $this->flash($this->trans('Tax rate successfully saved'), 'success');

            return $this->redirect($this->generateUrl('_tax_rates'));
        }

        return $this->render('CSBillTaxBundle:Default:add.html.twig', ['form' => $form->createView()]);
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function deleteAction(Request $request): Response
    {
        /** @var \CSBill\InvoiceBundle\Repository\ItemRepository $invoiceRepository */
        $invoiceRepository = $this->getRepository('CSBillInvoiceBundle:Item');

        /** @var \CSBill\QuoteBundle\Repository\ItemRepository $quoteRepository */
        $quoteRepository = $this->getRepository('CSBillQuoteBundle:Item');

        $data = $request->request->get('data');

        /** @var Tax[] $taxes */
        $taxes = $this->getRepository('CSBillTaxBundle:Tax')->findBy(['id' => $data]);

        $em = $this->getEm();
        foreach ($taxes as $tax) {
            $invoiceRepository->removeTax($tax);
            $quoteRepository->removeTax($tax);

            $em->remove($tax);
        }

        $em->flush();

        return $this->json([]);
    }

    /**
     * @param Tax $tax
     *
     * @return JsonResponse|Response
     */
    public function getAction(Tax $tax): Response
    {
        $result = [
            'id' => $tax->getId(),
            'name' => $tax->getName(),
            'type' => $tax->getType(),
            'rate' => $tax->getRate(),
        ];

        return $this->json($result);
    }
}
