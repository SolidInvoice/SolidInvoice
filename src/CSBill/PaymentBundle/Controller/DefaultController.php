<?php
/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\PaymentBundle\Controller;

use CSBill\CoreBundle\Controller\BaseController;
use CSBill\PaymentBundle\Grid\PaymentGrid;
use CSBill\PaymentBundle\Model\Status;

class DefaultController extends BaseController
{
    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     */
    public function listAction()
    {
        $grid = $this->get('grid')->create(new PaymentGrid());

        return $grid->getGridResponse(
            array(
                'status_list' => array(
                    Status::STATUS_UNKNOWN,
                    Status::STATUS_FAILED,
                    Status::STATUS_SUSPENDED,
                    Status::STATUS_EXPIRED,
                    Status::STATUS_PENDING,
                    Status::STATUS_CANCELLED,
                    Status::STATUS_NEW,
                    Status::STATUS_CAPTURED,
                    Status::STATUS_AUTHORIZED,
                    Status::STATUS_REFUNDED,
                ),
            )
        );
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction()
    {
        $paymentMethods = $this->get('payum')->getPaymentMethods();

        unset($paymentMethods['credit']);

        return $this->render(
            'CSBillPaymentBundle:Default:index.html.twig',
            array(
                'paymentMethods' => array_keys($paymentMethods),
            )
        );

    }
}
