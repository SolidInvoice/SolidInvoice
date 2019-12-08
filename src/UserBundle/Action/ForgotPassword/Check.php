<?php

declare(strict_types=1);

/*
 * This file is part of the SolidInvoice project.
 *
 * @author     pierre
 * @copyright  Copyright (c) 2019
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
