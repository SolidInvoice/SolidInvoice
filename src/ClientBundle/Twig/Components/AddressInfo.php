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
use SolidInvoice\ClientBundle\Form\Type\AddressType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;

#[AsLiveComponent]
final class AddressInfo extends AbstractController
{
    use DefaultActionTrait;
    use ComponentToolsTrait;
    use LiveCollectionTrait;

    #[LiveProp(writable: true, fieldName: 'formData')]
    private Address $address;

    #[LiveProp(writable: true, updateFromParent: true)]
    public bool $canDelete = false;

    #[LiveProp(writable: true)]
    public bool $edit = false;

    /**
     * Terrible hack to ensure that we don't overwrite the original Address object when editing a form.
     * Changes in the form will display on the screen. Even when cancelling the form, the changes will persist on the view page.
     * We need to create a clone of the original object for display purpose only, so that we can modify the object separately
     * when editing it through the form.
     */
    #[LiveProp]
    private Address $readonlyAddress;

    public function setAddress(Address $address): void
    {
        $this->address = $address;
        $this->readonlyAddress = clone $address;
    }

    public function getReadonlyAddress(): Address
    {
        return $this->readonlyAddress;
    }

    public function setReadonlyAddress(Address $address): void
    {
        // no-op
        //
        // Do not set the readonlyAddress here,
        // to ensure that we always only use a clone of the original object
    }

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(AddressType::class, $this->address);
    }

    public function address(): Address
    {
        return $this->readonlyAddress;
    }

    #[LiveAction()]
    public function delete(EntityManagerInterface $manager): void
    {
        $manager->remove($this->address);
        $manager->flush();

        $this->emit('addressDeleted');
        $this->dispatchBrowserEvent('modal:close');
    }

    #[LiveAction()]
    public function save(EntityManagerInterface $manager): void
    {
        $this->submitForm();

        /** @var Address $address */
        $address = $this->getForm()->getData();

        $manager->persist($address);
        $manager->flush();

        $this->edit = false;
        $this->readonlyAddress = clone $address;
    }
}
