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

#[ApiResource(operations: [])]
#[ORM\Table(name: InvoiceContact::TABLE_NAME)]
#[ORM\Entity]
class InvoiceContact
{
    final public const TABLE_NAME = 'invoice_contact';

    use CompanyAware;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Invoice::class, cascade: ['persist', 'remove'], inversedBy: 'users')]
    #[ORM\JoinColumn(name: 'invoice_id')]
    private Invoice $invoice;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Contact::class, cascade: ['persist', 'remove'], inversedBy: 'invoices')]
    #[ORM\JoinColumn(name: 'contact_id')]
    private Contact $contact;

    public function getInvoice(): Invoice
    {
        return $this->invoice;
    }

    public function getContact(): Contact
    {
        return $this->contact;
    }

    public function setInvoice(Invoice $invoice): self
    {
        $this->invoice = $invoice;

        return $this;
    }

    public function setContact(Contact $contact): self
    {
        $this->contact = $contact;

        return $this;
    }
}
