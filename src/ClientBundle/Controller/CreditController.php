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

namespace CSBill\ClientBundle\Controller;

use CSBill\ClientBundle\Entity\Client;
use CSBill\ClientBundle\Entity\Credit;
use CSBill\ClientBundle\Repository\CreditRepository;
use CSBill\CoreBundle\Controller\BaseController;
use Money\Money;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

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
        /** @var CreditRepository $clientRepository */
        $clientRepository = $this->getRepository('CSBillClientBundle:Credit');

        $client = $credit->getClient();

        $value = new Money((int) ($request->request->get('credit') * 100), $client->getCurrency() ? $client->getCurrency() : $this->get('currency'));

        $credits = $clientRepository->addCredit($client, $value);

        return $this->json(
            [
                'credit' => $this->get('csbill.money.formatter')->toFloat($credits->getValue()),
                'id' => $credits->getId(),
            ]
        );
    }

    /**
     * @param Request $request
     * @param Client  $client
     *
     * @return JsonResponse
     *
     * @throws BadRequestHttpException
     */
    public function creditAction(Request $request, Client $client)
    {
        $jsonResponse = function (Credit $credit) use ($client) {
            return $this->json(
                [
                    'credit' => $this->get('csbill.money.formatter')->toFloat($credit->getValue()),
                    'id' => $client->getId(),
                ]
            );
        };

        if ($request->isMethod('GET')) {
            return $jsonResponse($client->getCredit());
        }

        if ($request->isMethod('PUT')) {
            /** @var CreditRepository $repository */
            $repository = $this->getRepository('CSBillClientBundle:Credit');

            $value = new Money((int) ($request->request->get('credit') * 100), $client->getCurrency() ? $client->getCurrency() : $this->get('currency'));

            return $jsonResponse($repository->addCredit($client, $value));
        }

        throw new BadRequestHttpException();
    }

    /**
     * @param Client $client
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getCreditAction(Client $client)
    {
        $credit = $client->getCredit();

        return $this->json(
            [
                'credit' => $this->get('csbill.money.formatter')->toFloat($credit->getValue()),
                'id' => $credit->getId(),
            ]
        );
    }
}
