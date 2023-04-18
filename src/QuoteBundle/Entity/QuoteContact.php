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

namespace SolidInvoice\QuoteBundle\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\CoreBundle\Traits\Entity\CompanyAware;

/**
 * @ORM\Entity()
 * @ORM\Table(name="quote_contact")
 * @ApiResource(collectionOperations={}, itemOperations={})
 */
class QuoteContact
{
    use CompanyAware;

    /**
     * @ORM\Id()
     * @ORM\ManyToOne(targetEntity=Quote::class, inversedBy="users", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="quote_id")
     */
    private Quote $quote;

    /**
     * @ORM\Id()
     * @ORM\ManyToOne(targetEntity=Contact::class, cascade={"persist", "remove"}, inversedBy="quotes")
     * @ORM\JoinColumn(name="contact_id")
     */
    private Contact $contact;

    public function getQuote(): Quote
    {
        return $this->quote;
    }

    public function getContact(): Contact
    {
        return $this->contact;
    }

    public function setQuote(Quote $quote): self
    {
        $this->quote = $quote;

        return $this;
    }

    public function setContact(Contact $contact): self
    {
        $this->contact = $contact;

        return $this;
    }
}
