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
use SolidInvoice\CoreBundle\Repository\BillingTemplateRepository;
use SolidInvoice\CoreBundle\Response\FlashResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class Delete extends AbstractController
{
    public function __construct(
        private readonly BillingTemplateRepository $repository,
    ) {
    }

    public function __invoke(Request $request, BillingTemplate $template): Response
    {
        if (! $this->isCsrfTokenValid('billing_template_delete_' . $template->getId()?->toRfc4122(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token');
        }

        $route = $this->generateUrl('_billing_templates');

        if ($template->isSystem() || $template->isActive()) {
            return new class($route) extends RedirectResponse implements FlashResponse {
                public function getFlash(): Generator
                {
                    yield self::FLASH_ERROR => 'billing_templates.flash.delete_denied';
                }
            };
        }

        $this->repository->delete($template);

        return new class($route) extends RedirectResponse implements FlashResponse {
            public function getFlash(): Generator
            {
                yield self::FLASH_SUCCESS => 'billing_templates.flash.deleted';
            }
        };
    }
}
