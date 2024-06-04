<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\NotificationBundle\Action;

use SolidInvoice\CoreBundle\Templating\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

final class Integrations extends AbstractController
{
    public function __invoke(Request $request): Template
    {
        return new Template('@SolidInvoiceNotification/Integration/add.html.twig');
    }
}
