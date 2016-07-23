<?php
/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\PaymentBundle\Factory;

class PaymentFactories
{
    /**
     * @var array
     */
    private $factories;

    /**
     * @param array $factories
     */
    public function setGatewayFactories(array $factories)
    {
        $this->factories = $factories;
    }

    /**
     * @param string $gateway
     *
     * @return bool
     */
    public function isOffline($gateway)
    {
        return isset($this->factories[$gateway]) && $this->factories[$gateway] === 'offline';
    }
}