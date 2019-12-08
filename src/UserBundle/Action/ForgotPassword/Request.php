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

final class Request
{
    public function __invoke()
    {
        return new Template('@SolidInvoiceUser/ForgotPassword/request.html.twig');
    }
}
