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
use CSBill\ClientBundle\Form\Type\CreditType;
use CSBill\ClientBundle\Repository\CreditRepository;
use CSBill\CoreBundle\Controller\BaseController;
use Symfony\Component\HttpFoundation\Request;

class CreditController extends BaseController
{
    /**
     * @param Request $request
     * @param Client  $client
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function addAction(Request $request, Client $client)
    {
        $form = $this->createForm(
            new CreditType(),
            null,
            array(
                'action' => $this->generateUrl('_clients_add_credit', array('id' => $client->getId())),
            )
        );

        $form->handleRequest($request);

        if ($form->isValid()) {
            /** @var CreditRepository $clientRepository */
            $clientRepository = $this->getRepository('CSBillClientBundle:Credit');

            $credit = $clientRepository->addCredit($client, $form->get('amount')->getData());

            return $this->json(
                array(
                    'status' => 'success',
                    'amount' => $credit->getValue(),
                )
            );
        }

        $content = $this->renderView(
            'CSBillClientBundle:Ajax:credit_add.html.twig',
            array(
                'form' => $form->createView(),
            )
        );

        return $this->json(array('content' => $content));
    }
}
