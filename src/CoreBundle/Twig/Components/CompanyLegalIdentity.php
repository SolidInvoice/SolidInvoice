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

namespace SolidInvoice\CoreBundle\Twig\Components;

use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\CoreBundle\Form\Type\CompanyLegalIdentityType;
use SolidInvoice\CoreBundle\Repository\CompanyRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Uid\Ulid;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class CompanyLegalIdentity extends AbstractController
{
    use ComponentWithFormTrait;
    use DefaultActionTrait;

    public function __construct(
        private readonly CompanyRepository $repository,
        private readonly CompanySelector $companySelector,
        private readonly EntityManagerInterface $em,
    ) {
    }

    /**
     * @return FormInterface<mixed>
     */
    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(CompanyLegalIdentityType::class, $this->getCompany());
    }

    #[LiveAction]
    public function save(): RedirectResponse
    {
        $this->submitForm();

        $this->em->flush();
        $this->addFlash('success', 'settings.saved.success');

        return $this->redirectToRoute('_settings', ['section' => 'system']);
    }

    private function getCompany(): Company
    {
        $companyId = $this->companySelector->getCompany();

        if (! $companyId instanceof Ulid) {
            throw new LogicException('Cannot edit the company legal identity without an active company context.');
        }

        $company = $this->repository->find($companyId);

        if (! $company instanceof Company) {
            throw new LogicException('Cannot edit the company legal identity: active company could not be loaded.');
        }

        return $company;
    }
}
