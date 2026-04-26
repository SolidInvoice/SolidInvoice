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

namespace SolidInvoice\SettingsBundle\Action\CustomField;

use Doctrine\ORM\EntityManagerInterface;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\CoreBundle\Entity\CustomField\CustomField;
use SolidInvoice\CoreBundle\Enum\CustomFieldTarget;
use SolidInvoice\CoreBundle\Repository\CustomFieldRepository;
use SolidInvoice\SettingsBundle\Form\Type\CustomFieldDefinitionType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class CreateAction extends AbstractController
{
    public function __construct(
        private readonly CustomFieldRepository $fields,
        private readonly EntityManagerInterface $em,
        private readonly CompanySelector $companies,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $target = CustomFieldTarget::from((string) $request->query->get('target', CustomFieldTarget::CLIENT->value));

        $field = (new CustomField())->setTarget($target);

        $form = $this->createForm(CustomFieldDefinitionType::class, $field);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $field->setPosition($this->fields->nextPosition($field->getTarget()));

            $companyId = $this->companies->getCompany();
            if ($companyId === null) {
                throw $this->createAccessDeniedException('No company in scope.');
            }
            $company = $this->em->getReference(Company::class, $companyId);
            $field->setCompany($company);

            $this->em->persist($field);
            $this->em->flush();

            $this->addFlash('success', 'Custom field created.');
            return new RedirectResponse($this->generateUrl('_settings_custom_fields'));
        }

        return $this->render('@SolidInvoiceSettings/CustomField/edit.html.twig', [
            'form' => $form->createView(),
            'mode' => 'create',
            'usageCount' => 0,
        ]);
    }
}
