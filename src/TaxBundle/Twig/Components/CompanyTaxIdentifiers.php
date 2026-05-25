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

namespace SolidInvoice\TaxBundle\Twig\Components;

use Doctrine\ORM\EntityManagerInterface;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\TaxBundle\Entity\TaxIdentifier;
use SolidInvoice\TaxBundle\Form\Type\CompanyTaxIdentifiersFormType;
use SolidInvoice\TaxBundle\Repository\TaxIdentifierRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Uid\Ulid;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;

#[AsLiveComponent]
final class CompanyTaxIdentifiers extends AbstractController
{
    use DefaultActionTrait;
    use LiveCollectionTrait;

    public function __construct(
        private readonly TaxIdentifierRepository $repository,
        private readonly CompanySelector $companySelector,
        private readonly EntityManagerInterface $em,
    ) {
    }

    /**
     * @return FormInterface<mixed>
     */
    protected function instantiateForm(): FormInterface
    {
        $companyId = $this->companySelector->getCompany();
        $identifiers = $companyId instanceof Ulid
            ? $this->repository->findCompanyIdentifiers($companyId)
            : [];

        return $this->createForm(CompanyTaxIdentifiersFormType::class, [
            'identifiers' => $identifiers,
        ]);
    }

    #[LiveAction]
    public function save(): RedirectResponse
    {
        $this->submitForm();
        /** @var array{identifiers: list<TaxIdentifier>} $data */
        $data = $this->getForm()->getData();
        $submitted = $data['identifiers'] ?? [];
        $companyId = $this->companySelector->getCompany();
        $existing = $companyId instanceof Ulid ? $this->repository->findCompanyIdentifiers($companyId) : [];
        $submittedIds = [];
        foreach ($submitted as $identifier) {
            $identifier->setClient(null);
            $this->em->persist($identifier);

            if ($identifier->getId() !== null) {
                $submittedIds[(string) $identifier->getId()] = true;
            }
        }

        foreach ($existing as $identifier) {
            if (! isset($submittedIds[(string) $identifier->getId()])) {
                $this->em->remove($identifier);
            }
        }

        $this->em->flush();
        $this->addFlash('success', 'settings.saved.success');
        return $this->redirectToRoute('_settings', ['section' => 'system']);
    }
}
