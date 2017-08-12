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

namespace CSBill\ClientBundle\Action\Ajax;

use CSBill\ClientBundle\Entity\Address as Entity;
use CSBill\CoreBundle\Response\AjaxResponse;
use CSBill\CoreBundle\Traits\SerializeTrait;
use Symfony\Component\HttpFoundation\Request;

final class Address implements AjaxResponse
{
    use SerializeTrait;

    public function __invoke(Request $request, Entity $address)
    {
        return $this->serialize($address, ['client_api']);
    }
}
