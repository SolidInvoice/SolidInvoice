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

namespace SolidInvoice\ClientBundle\Twig\Components;

use Doctrine\ORM\EntityManagerInterface;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\ClientBundle\Form\Type\ContactType;
use SolidInvoice\CoreBundle\Enum\CustomFieldTarget;
use SolidInvoice\CoreBundle\Service\CustomField\CustomFieldFormWriter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;

/**
 * @see \SolidInvoice\ClientBundle\Tests\Twig\Components\ContactInfoTest
 */
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

    public function __construct(
        private readonly EntityManagerInterface $manager,
        private readonly CustomFieldFormWriter $customFieldFormWriter,
    ) {
    }

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

    /**
     * @return FormInterface<mixed>
     */
    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(ContactType::class, $this->contact);
    }

    public function contact(): Contact
    {
        return $this->readonlyContact;
    }

    #[LiveAction()]
    public function delete(): void
    {
        $this->manager->remove($this->contact);
        $this->manager->flush();
        $this->emit('contactDeleted');
        $this->dispatchBrowserEvent('modal:close');
    }

    #[LiveAction()]
    public function save(): void
    {
        $this->submitForm();
        /** @var Contact $contact */
        $contact = $this->getForm()->getData();
        $this->manager->persist($contact);
        $this->manager->flush();

        $form = $this->getForm();
        if ($form->has('customFields')) {
            $this->customFieldFormWriter->write(
                $form->get('customFields'),
                CustomFieldTarget::CONTACT,
                $contact->getId(),
                $contact,
            );
            $this->manager->flush();
        }

        $this->edit = false;
        $this->readonlyContact = clone $contact;
    }
}
