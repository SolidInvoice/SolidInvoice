<?php

declare(strict_types = 1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\ClientBundle\Action\Ajax;

use SolidInvoice\ClientBundle\Entity\Contact as Entity;
use SolidInvoice\CoreBundle\Response\AjaxResponse;
use SolidInvoice\CoreBundle\Traits\SerializeTrait;
use Symfony\Component\HttpFoundation\Request;

final class Contact implements AjaxResponse
{
    use SerializeTrait;

    public function __invoke(Request $request, Entity $contact)
    {
        return $this->serialize($contact);
    }
}
