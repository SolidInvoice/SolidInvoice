<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\ClientBundle\Twig\Components;

use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Form\Type\CreditType;
use SolidInvoice\ClientBundle\Repository\CreditRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class ClientCredit extends AbstractController
{
    use DefaultActionTrait;
    use ComponentToolsTrait;
    use ComponentWithFormTrait;

    #[LiveProp(writable: true)]
    public Client $client;

    public function __construct(
        private readonly CreditRepository $repository,
    ) {
    }

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(CreditType::class);
    }

    #[LiveAction()]
    public function save(): void
    {
        $this->submitForm();

        $data = $this->getForm()->getData();

        $this->repository->addCredit($this->client, $data['amount'] ?? 0);

        $this->dispatchBrowserEvent('modal:close');

        $this->resetForm();
    }
}
