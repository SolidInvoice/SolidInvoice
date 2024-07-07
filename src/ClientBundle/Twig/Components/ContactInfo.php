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
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\ClientBundle\Form\Type\ContactType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;

#[AsLiveComponent]
final class ContactInfo extends AbstractController
{
    use DefaultActionTrait;
    use ComponentToolsTrait;
    use LiveCollectionTrait;

    #[LiveProp(writable: true, fieldName: 'formData')]
    private Contact $contact;

    #[LiveProp(writable: true, updateFromParent: true)]
    public bool $canDelete = false;

    #[LiveProp(writable: true)]
    public bool $edit = false;

    /**
     * Terrible hack to ensure that we don't overwrite the original Contact object when editing a form.
     * Changes in the form will display on the screen. Even when cancelling the form, the changes will persist on the view page.
     * We need to create a clone of the original object for display purpose only, so that we can modify the object separately
     * when editing it through the form.
     */
    #[LiveProp]
    private Contact $readonlyContact;

    public function setContact(Contact $contact): void
    {
        $this->contact = $contact;
        $this->readonlyContact = clone $contact;
    }

    public function getReadonlyContact(): Contact
    {
        return $this->readonlyContact;
    }

    public function setReadonlyContact(Contact $contact): void
    {
        // no-op
        //
        // Do not set the readonlyContact here,
        // to ensure that we always only use a clone of the original object
    }

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(ContactType::class, $this->contact);
    }

    public function contact(): Contact
    {
        return $this->readonlyContact;
    }

    #[LiveAction()]
    public function delete(EntityManagerInterface $manager): void
    {
        $manager->remove($this->contact);
        $manager->flush();

        $this->emit('contactDeleted');
        $this->dispatchBrowserEvent('modal:close');
    }

    #[LiveAction()]
    public function save(EntityManagerInterface $manager): void
    {
        $this->submitForm();

        /** @var Contact $contact */
        $contact = $this->getForm()->getData();

        $manager->persist($contact);
        $manager->flush();

        $this->edit = false;
        $this->readonlyContact = clone $contact;
    }
}
