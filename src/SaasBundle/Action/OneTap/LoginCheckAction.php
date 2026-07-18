<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\SaasBundle\Action\OneTap;

use LogicException;

/**
 * Target of the `_onetap_login_check` route. The request is intercepted and
 * handled by Symfony's login_link authenticator on the `main` firewall, so this
 * controller is never actually executed.
 */
final class LoginCheckAction
{
    public function __invoke(): never
    {
        throw new LogicException('The login-link check route is handled by the security firewall and must never reach this controller.');
    }
}
