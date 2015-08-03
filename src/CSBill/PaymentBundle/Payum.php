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
     * @return array
     */
    public function getGatewayList()
    {
        return array_keys($this->gateways);
    }
}
