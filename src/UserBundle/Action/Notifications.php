<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\UserBundle\Action;

use SolidInvoice\CoreBundle\Templating\Template;
use SolidInvoice\UserBundle\Form\Type\NotificationType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

final class Notifications extends AbstractController
{
    public function __invoke(Request $request): Template
    {
        $form = $this->createForm(NotificationType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            dd($data);
        }

        return new Template('@SolidInvoiceUser/Notifications/index.html.twig', ['form' => $form->createView()]);
    }
}
