<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\ClientBundle\Action\Ajax\Address;

use CSBill\ClientBundle\Entity\Client;
use CSBill\CoreBundle\Traits\SerializeTrait;
use Symfony\Component\HttpFoundation\Request;

final class AddressList
{
    use SerializeTrait;

    public function __invoke(Request $request, Client $client)
    {
        return $this->serialize($client->getAddresses());
    }
}