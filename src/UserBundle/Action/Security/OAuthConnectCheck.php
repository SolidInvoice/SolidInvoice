<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\UserBundle\Action\Security;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class OAuthConnectCheck extends AbstractController
{
    public const ROUTE = '_oauth_connect_check';

    public function __invoke(): never
    {
        // The firewall should intercept this route and do the authentication,
        // so this controller should never be hit

        throw $this->createNotFoundException();
    }
}
