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

namespace SolidInvoice\SettingsBundle\Twig\Components;

use Doctrine\ORM\EntityManagerInterface;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\CoreBundle\Entity\CustomField\CustomField;
use SolidInvoice\CoreBundle\Enum\CustomFieldType;
use SolidInvoice\CoreBundle\Repository\CustomFieldRepository;
use SolidInvoice\CoreBundle\Repository\CustomFieldValueRepository;
use SolidInvoice\SaasBundle\Feature\Feature;
use SolidInvoice\SettingsBundle\Form\Type\CustomFieldDefinitionType;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Uid\Ulid;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;

#[AsLiveComponent]
final class CustomFieldForm extends AbstractController
{
    use DefaultActionTrait;
    use LiveCollectionTrait;

    #[LiveProp(fieldName: 'formData')]
    public ?CustomField $field = null;

    #[LiveProp]
    public bool $editing = false;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CustomFieldRepository $fields,
        private readonly CustomFieldValueRepository $values,
        private readonly CompanySelector $companies,
        private readonly FeatureGate $featureGate,
    ) {
    }

    public function mode(): string
    {
        return $this->editing ? 'edit' : 'create';
    }

    public function usageCount(): int
    {
        if (! $this->editing || ! $this->field instanceof CustomField) {
            return 0;
        }

        return $this->values->countByField($this->field);
    }

    /**
     * @return FormInterface<mixed>
     */
    protected function instantiateForm(): FormInterface
    {
        $field = $this->field ?? new CustomField()->setType(CustomFieldType::TEXT);

        return $this->createForm(CustomFieldDefinitionType::class, $field, [
            'lock_target' => $this->editing,
        ]);
    }

    #[LiveAction]
    public function save(): RedirectResponse
    {
        if (! $this->featureGate->isEnabled(Feature::CustomFields->value)) {
            throw $this->createAccessDeniedException('Custom fields are not available on the current plan.');
        }

        $this->submitForm();

        /** @var CustomField $field */
        $field = $this->getForm()->getData();

        if (! $this->editing) {
            $field->setPosition($this->fields->nextPosition($field->getTarget()));

            $companyId = $this->companies->getCompany();
            if (! $companyId instanceof Ulid) {
                throw $this->createAccessDeniedException('No company in scope.');
            }

            $field->setCompany($this->em->getReference(Company::class, $companyId));

            $this->em->persist($field);
        }

        $this->em->flush();

        $this->addFlash('success', $this->editing ? 'Custom field updated.' : 'Custom field created.');

        return $this->redirectToRoute('_settings_custom_fields');
    }
}
