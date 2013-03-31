<?php

namespace CSBill\ItemBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('CSBillItemBundle:Default:index.html.twig', array());
    }
}
