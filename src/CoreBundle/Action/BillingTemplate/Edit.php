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

namespace SolidInvoice\CoreBundle\Action\BillingTemplate;

use Generator;
use SolidInvoice\CoreBundle\Entity\BillingTemplate;
use SolidInvoice\CoreBundle\Form\Type\BillingTemplateType;
use SolidInvoice\CoreBundle\Repository\BillingTemplateRepository;
use SolidInvoice\CoreBundle\Response\FlashResponse;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class Edit extends AbstractController
{
    public function __construct(
        private readonly BillingTemplateRepository $repository,
    ) {
    }

    /**
     * @return array{form: \Symfony\Component\Form\FormView, template: BillingTemplate, is_new: bool}|Response
     */
    #[Template('@SolidInvoiceCore/BillingTemplate/form.html.twig')]
    public function __invoke(Request $request, BillingTemplate $template): array|Response
    {
        $form = $this->createForm(BillingTemplateType::class, $template);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->repository->save($template);

            $route = $this->generateUrl('_billing_templates');

            return new class($route) extends RedirectResponse implements FlashResponse {
                public function getFlash(): Generator
                {
                    yield self::FLASH_SUCCESS => 'billing_templates.flash.updated';
                }
            };
        }

        return [
            'form' => $form->createView(),
            'template' => $template,
            'is_new' => false,
        ];
    }
}
