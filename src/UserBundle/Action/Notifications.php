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

namespace SolidInvoice\UserBundle\Action;

use SolidInvoice\UserBundle\Form\Type\NotificationType;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;

final class Notifications extends AbstractController
{
    /**
     * @return array{form: FormView}
     */
    #[Template('@SolidInvoiceUser/Notifications/index.html.twig')]
    public function __invoke(Request $request): array
    {
        $form = $this->createForm(NotificationType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            dd($data);
        }

        return ['form' => $form->createView()];
    }
}
