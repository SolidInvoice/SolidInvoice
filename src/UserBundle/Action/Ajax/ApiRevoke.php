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

namespace SolidInvoice\UserBundle\Action\Ajax;

use SolidInvoice\CoreBundle\Response\AjaxResponse;
use SolidInvoice\CoreBundle\Traits\DoctrineAwareTrait;
use SolidInvoice\CoreBundle\Traits\JsonTrait;
use SolidInvoice\UserBundle\Entity\ApiToken;
use Symfony\Component\HttpFoundation\Request;

final class ApiRevoke implements AjaxResponse
{
    use DoctrineAwareTrait;
    use
        JsonTrait;

    public function __invoke(Request $request, ApiToken $token)
    {
        $em = $this->doctrine->getManager();
        $em->remove($token);
        $em->flush();

        return $this->json([]);
    }
}
