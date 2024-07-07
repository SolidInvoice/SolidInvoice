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

use Doctrine\ORM\EntityManagerInterface;
use SolidInvoice\ClientBundle\Entity\Address;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Form\Type\AddressType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;

#[AsLiveComponent]
final class AddressCollection extends AbstractController
{
    use DefaultActionTrait;
    use LiveCollectionTrait;
    use ComponentToolsTrait;

    #[LiveProp(writable: true)]
    public Client $client;

    public int $count = 0;

    #[LiveListener('addressDeleted')]
    public function setAddressCount(): void
    {
        $this->count = count($this->client->getAddresses());
    }

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(AddressType::class, (new Address())->setClient($this->client));
    }

    #[LiveAction()]
    public function save(EntityManagerInterface $manager): void
    {
        $this->submitForm();

        /** @var Address $address */
        $address = $this->getForm()->getData();
        $this->client->addAddress($address);

        $manager->persist($address);
        $manager->flush();

        $this->setAddressCount();
        $this->dispatchBrowserEvent('modal:close');

        $this->resetForm();
    }
}
