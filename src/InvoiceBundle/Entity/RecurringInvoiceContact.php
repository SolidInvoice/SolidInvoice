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
 * @ORM\Table(name="recurringinvoice_contact")
 * @ApiResource(itemOperations={}, collectionOperations={})
 */
class RecurringInvoiceContact
{
    use CompanyAware;

    /**
     * @ORM\Id()
     * @ORM\ManyToOne(targetEntity=RecurringInvoice::class, inversedBy="users", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="recurringinvoice_id", referencedColumnName="id")
     */
    private RecurringInvoice $recurringInvoice;

    /**
     * @ORM\Id()
     * @ORM\ManyToOne(targetEntity=Contact::class, cascade={"persist", "remove"}, inversedBy="recurringInvoices")
     * @ORM\JoinColumn(name="contact_id", referencedColumnName="id")
     */
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
