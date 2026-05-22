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

use Doctrine\Persistence\ManagerRegistry;
use Generator;
use InvalidArgumentException;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Entity\BillingTemplate;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\CoreBundle\Form\Type\BillingTemplateType;
use SolidInvoice\CoreBundle\Repository\BillingTemplateRepository;
use SolidInvoice\CoreBundle\Response\FlashResponse;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use function in_array;
use function strtolower;

final class Create extends AbstractController
{
    public function __construct(
        private readonly BillingTemplateRepository $repository,
        private readonly ManagerRegistry $registry,
        private readonly CompanySelector $companySelector,
    ) {
    }

    /**
     * @return array{form: \Symfony\Component\Form\FormView, template: BillingTemplate, is_new: bool}|Response
     */
    #[Template('@SolidInvoiceCore/BillingTemplate/form.html.twig')]
    public function __invoke(Request $request, string $type, string $variant): array|Response
    {
        $type = strtolower($type);
        $variant = strtolower($variant);

        if (! in_array($type, [BillingTemplate::TYPE_INVOICE, BillingTemplate::TYPE_QUOTE], true)) {
            throw new InvalidArgumentException(sprintf('Unsupported template type "%s"', $type));
        }

        if (! in_array($variant, [BillingTemplate::VARIANT_HTML, BillingTemplate::VARIANT_PDF, BillingTemplate::VARIANT_EMAIL], true)) {
            throw new InvalidArgumentException(sprintf('Unsupported template variant "%s"', $variant));
        }

        $template = new BillingTemplate();
        $template->setType($type);
        $template->setVariant($variant);

        // Pre-populate with the active or system template as a starting point.
        $base = $this->repository->findActive($type, $variant) ?? $this->repository->findSystemTemplate($type, $variant);
        if ($base instanceof BillingTemplate) {
            $template->setContent($base->getContent());
        }

        $company = $this->resolveCompany();
        if (! $company instanceof Company) {
            throw $this->createAccessDeniedException();
        }
        $template->setCompany($company);

        $form = $this->createForm(BillingTemplateType::class, $template);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->repository->save($template);

            $route = $this->generateUrl('_billing_templates');

            return new class($route) extends RedirectResponse implements FlashResponse {
                public function getFlash(): Generator
                {
                    yield self::FLASH_SUCCESS => 'billing_templates.flash.created';
                }
            };
        }

        return [
            'form' => $form->createView(),
            'template' => $template,
            'is_new' => true,
        ];
    }

    private function resolveCompany(): ?Company
    {
        $companyId = $this->companySelector->getCompany();

        if (null === $companyId) {
            return null;
        }

        $company = $this->registry->getManager()->getRepository(Company::class)->find($companyId);

        return $company instanceof Company ? $company : null;
    }
}
