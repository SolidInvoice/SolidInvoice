<?php

/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace CSBill\PaymentBundle\Twig;

use Doctrine\Common\Persistence\ManagerRegistry;
use Twig_Extension;
use Twig_SimpleFunction;

class PaymentExtension extends Twig_Extension
{
    /**
     * @var \CSBill\PaymentBundle\Repository\PaymentMethodRepository
     */
    private $repository;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->repository = $registry->getRepository('CSBillPaymentBundle:PaymentMethod');
    }

    /**
     * @return Twig_SimpleFunction[]
     */
    public function getFunctions()
    {
        return array(
            new Twig_SimpleFunction('payment_enabled', array($this, 'paymentEnabled')),
        );
    }

    /**
     * @param string $method
     *
     * @return bool
     */
    public function paymentEnabled($method)
    {
        $paymentMethod = $this->repository->findOneBy(array('paymentMethod' => $method));

        if (null === $paymentMethod) {
            return false;
        }

        return $paymentMethod->isEnabled();
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'payment_extension';
    }
}
