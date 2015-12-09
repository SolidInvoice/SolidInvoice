<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\PaymentBundle;

use Payum\Bundle\PayumBundle\Registry\ContainerAwareRegistry;

class Payum extends ContainerAwareRegistry
{
    /**
     * @var array
     */
    private $factories;

    /**
     * @return array
     */
    public function getGatewayList()
    {
        $list = array_keys($this->gateways);

        sort($list, SORT_ASC | SORT_STRING | SORT_FLAG_CASE);

        return $list;
    }

    /**
     * @param array $factories
     */
    public function setGatewayFactories(array $factories)
    {
        $this->factories = $factories;
    }

    /**
     * @param string $paymentMethod
     *
     * @return bool
     */
    public function isOffline($paymentMethod)
    {
        return isset($this->factories[$paymentMethod]) && $this->factories[$paymentMethod] === 'offline';
    }
}
