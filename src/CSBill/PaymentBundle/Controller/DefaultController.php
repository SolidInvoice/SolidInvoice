<?php

namespace CSBill\PaymentBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('CSBillPaymentBundle:Default:index.html.twig', array());
    }
}
