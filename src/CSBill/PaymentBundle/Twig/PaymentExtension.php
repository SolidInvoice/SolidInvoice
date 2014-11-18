<?php

namespace CSBill\PaymentBundle\Twig;

use CSBill\PaymentBundle\Manager\PaymentMethodManager;
use Twig_Extension;
use Twig_SimpleFunction;

class PaymentExtension extends Twig_Extension
{
    /**
     * @var PaymentMethodManager
     */
    private $paymentsManager;

    /**
     * @param PaymentMethodManager $paymentsManager
     */
    public function __construct(PaymentMethodManager $paymentsManager)
    {
        $this->paymentsManager = $paymentsManager;
    }

    /**
     * @return Twig_SimpleFunction[]
     */
    public function getFunctions()
    {
        return array(
            new Twig_SimpleFunction('paymentsConfigured', array($this, 'paymentsConfigured')),
        );
    }

    /**
     * @return bool
     */
    public function paymentsConfigured()
    {
        return count($this->paymentsManager) > 0;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'payment_extension';
    }
}
