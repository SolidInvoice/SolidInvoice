<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\PaymentBundle\Controller;

use CSBill\CoreBundle\Controller\BaseController;
use CSBill\PaymentBundle\Entity\PaymentMethod;
use CSBill\PaymentBundle\Repository\PaymentMethodRepository;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends BaseController
{
    /**
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Exception
     */
    public function listAction()
    {
        return $this->render('CSBillPaymentBundle:Default:list.html.twig');
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            /** @var \CSBill\PaymentBundle\Payum $payum */
            $payum = $this->get('payum');

            $paymentMethods = $payum->getGatewayList();

            /** @var PaymentMethodRepository $repository */
            $repository = $this->getRepository('CSBillPaymentBundle:PaymentMethod');

            $enabledMethods = array_map(
                function (PaymentMethod $method) {
                    return strtolower($method->getPaymentMethod());
                }, $repository->findBy(['enabled' => 1])
            );

            return $this->json(
                [
                    'enabled' => array_intersect($paymentMethods, $enabledMethods),
                    'disabled' => array_diff($paymentMethods, $enabledMethods),
                ]
            );
        }

        return $this->render('CSBillPaymentBundle:Default:index.html.twig');
    }
}
