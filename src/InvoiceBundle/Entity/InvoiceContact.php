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

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\CoreBundle\Traits\Entity\CompanyAware;

/**
 * @ORM\Entity()
 * @ORM\Table(name="invoice_contact")
 * @ApiResource(collectionOperations={}, itemOperations={})
 */
class InvoiceContact
{
    use CompanyAware;

    /**
     * @ORM\Id()
     * @ORM\ManyToOne(targetEntity=Invoice::class, inversedBy="users", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="invoice_id", referencedColumnName="id")
     */
    private Invoice $invoice;

    /**
     * @ORM\Id()
     * @ORM\ManyToOne(targetEntity=Contact::class, cascade={"persist", "remove"}, inversedBy="invoices")
     * @ORM\JoinColumn(name="contact_id", referencedColumnName="id")
     */
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
