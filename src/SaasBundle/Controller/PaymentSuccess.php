<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\SaasBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class PaymentSuccess extends AbstractController
{
    public function __invoke(): Response
    {
        $this->addFlash('success', 'Your subscription has been activated.');

        return $this->redirectToRoute('_dashboard');
    }
}
