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
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\ClientBundle\Form\Type\ContactType;
use SolidInvoice\CoreBundle\Enum\CustomFieldTarget;
use SolidInvoice\CoreBundle\Service\CustomField\CustomFieldFormWriter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;

/**
 * @see \SolidInvoice\ClientBundle\Tests\Twig\Components\ContactCollectionTest
 */
#[AsLiveComponent]
final class ContactCollection extends AbstractController
{
    use DefaultActionTrait;
    use LiveCollectionTrait;
    use ComponentToolsTrait;

    #[LiveProp(writable: true)]
    public Client $client;

    public int $count = 0;

    public function __construct(
        private readonly EntityManagerInterface $manager,
        private readonly CustomFieldFormWriter $customFieldFormWriter,
    ) {
    }

    #[LiveListener('contactDeleted')]
    public function setContactCount(): void
    {
        $this->count = count($this->client->getContacts());
    }

    /**
     * @return FormInterface<mixed>
     */
    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(ContactType::class, new Contact()->setClient($this->client));
    }

    #[LiveAction()]
    public function save(): void
    {
        $this->submitForm();
        /** @var Contact $contact */
        $contact = $this->getForm()->getData();
        $this->client->addContact($contact);
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

        $this->setContactCount();
        $this->dispatchBrowserEvent('modal:close');
        $this->resetForm();
    }
}
