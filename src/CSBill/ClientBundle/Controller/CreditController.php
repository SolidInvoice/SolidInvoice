<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\ClientBundle\Controller;

use CSBill\ClientBundle\Entity\Client;
use CSBill\ClientBundle\Entity\Credit;
use CSBill\ClientBundle\Form\Type\CreditType;
use CSBill\ClientBundle\Repository\CreditRepository;
use CSBill\CoreBundle\Controller\BaseController;
use Money\Money;
use Symfony\Component\HttpFoundation\Request;

class CreditController extends BaseController
{
    /**
     * @param Request $request
     * @param Credit  $credit
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function addAction(Request $request, Credit $credit)
    {
        $value = new Money((int) ($request->request->get('credit') * 100), $this->get('currency'));

        /** @var CreditRepository $clientRepository */
        $clientRepository = $this->getRepository('CSBillClientBundle:Credit');

        $credits = $clientRepository->addCredit($credit->getClient(), $value);

        return $this->json([
            'credit' => $this->get('csbill.money.formatter')->toFloat($credits->getValue()),
            'id' => $credits->getId(),
        ]);
    }
}
