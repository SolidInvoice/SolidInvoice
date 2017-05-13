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

namespace CSBill\UserBundle\Action\Ajax;

use CSBill\CoreBundle\Response\AjaxResponse;
use CSBill\CoreBundle\Traits\DoctrineAwareTrait;
use CSBill\CoreBundle\Traits\JsonTrait;
use CSBill\UserBundle\Entity\ApiToken;
use Symfony\Component\HttpFoundation\Request;

final class ApiRevoke implements AjaxResponse
{
    use DoctrineAwareTrait,
        JsonTrait;

    public function __invoke(Request $request, ApiToken $token)
    {
        $em = $this->doctrine->getManager();
        $em->remove($token);
        $em->flush();

        return $this->json([]);
    }
}
