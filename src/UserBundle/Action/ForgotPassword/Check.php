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

namespace SolidInvoice\UserBundle\Action\ForgotPassword;

use SolidInvoice\CoreBundle\Templating\Template;

final class Check
{
    public function __invoke()
    {
        return new Template('@SolidInvoiceUser/ForgotPassword/check_email.html.twig', ['tokenLifetime' => ceil((60 * 60 * 3) / 3600)]);
    }
}
