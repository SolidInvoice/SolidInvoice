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

use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class TwoFactorIndex extends AbstractController
{
    #[Template('@SolidInvoiceUser/Security/TwoFactor/index.html.twig')]
    public function __invoke(): array
    {
        return [];
    }
}
