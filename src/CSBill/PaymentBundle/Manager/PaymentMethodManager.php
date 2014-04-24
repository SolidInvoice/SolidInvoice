<?php

namespace CSBill\PaymentBundle\Manager;

use Payum\Bundle\PayumBundle\Registry\ContainerAwareRegistry;
use CSBill\PaymentBundle\Payment\Method;

class PaymentMethodManager
{
    /**
     * @var array
     */
    protected $methods = array();

    /**
     * @var ContainerAwareRegistry
     */
    protected $payum;

    /**
     * @param ContainerAwareRegistry $payum
     */
    public function __construct(ContainerAwareRegistry $payum)
    {
        $this->payum = $payum;
    }

    /**
     * @param string $name
     * @param string $context
     * @param array  $settings
     */
    public function addPaymentMethod($name, $context, array $settings = array())
    {
        $this->methods[$name] = new Method($name, $context, $settings);
    }

    /**
     * @return array
     */
    public function getPaymentMethods()
    {
        return $this->methods;
    }

    /**
     * @param string $method
     *
     * @throws \Exception
     * @return Method
     */
    public function getPaymentMethod($method)
    {
        if (!isset($this->methods[$method])) {
            throw new \Exception(sprintf('The payment method %s is invalid', $method));
        }

        return $this->methods[$method];
    }
}
