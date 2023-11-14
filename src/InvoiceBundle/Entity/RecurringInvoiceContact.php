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

namespace SolidInvoice\InvoiceBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\CoreBundle\Traits\Entity\CompanyAware;
use SolidInvoice\InvoiceBundle\Repository\RecurringInvoiceContactRepository;

#[ApiResource(operations: [])]
#[ORM\Table(name: RecurringInvoiceContact::TABLE_NAME)]
#[ORM\Entity(repositoryClass: RecurringInvoiceContactRepository::class)]
class RecurringInvoiceContact
{
    final public const TABLE_NAME = 'recurringinvoice_contact';

    use CompanyAware;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: RecurringInvoice::class, cascade: ['persist', 'remove'], inversedBy: 'users')]
    #[ORM\JoinColumn(name: 'recurringinvoice_id')]
    private RecurringInvoice $recurringInvoice;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Contact::class, cascade: ['persist', 'remove'], inversedBy: 'recurringInvoices')]
    #[ORM\JoinColumn(name: 'contact_id')]
    private Contact $contact;

    public function getRecurringInvoice(): RecurringInvoice
    {
        return $this->recurringInvoice;
    }

    public function getContact(): Contact
    {
        return $this->contact;
    }

    public function setRecurringInvoice(RecurringInvoice $recurringInvoice): self
    {
        $this->recurringInvoice = $recurringInvoice;

        return $this;
    }

    public function setContact(Contact $contact): self
    {
        $this->contact = $contact;

        return $this;
    }
}
