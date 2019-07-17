<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\ClientBundle\Action\Ajax\Address;

use SolidInvoice\ClientBundle\Entity\Address;
use SolidInvoice\CoreBundle\Response\AjaxResponse;
use SolidInvoice\CoreBundle\Traits\DoctrineAwareTrait;
use SolidInvoice\CoreBundle\Traits\JsonTrait;

final class Delete implements AjaxResponse
{
    use DoctrineAwareTrait;
    use
        JsonTrait;

    public function __invoke(Address $address)
    {
        $em = $this->doctrine->getManager();

        $em->remove($address);
        $em->flush();

        return $this->json([]);
    }
}
