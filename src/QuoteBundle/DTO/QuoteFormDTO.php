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

namespace SolidInvoice\QuoteBundle\DTO;

use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\ClientBundle\Validator\Constraints\UniqueClientName;
use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\QuoteBundle\Entity\Line;
use SolidInvoice\QuoteBundle\Enum\QuoteClientMode;
use SolidInvoice\TaxBundle\Entity\InvoiceTax;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO for Quote form data
 */
final class QuoteFormDTO
{
    public QuoteClientMode $clientMode = QuoteClientMode::Existing;

    // Existing client selection (mode=Existing)
    #[Assert\NotBlank(groups: ['existing_client'])]
    public ?Client $client = null;

    // Inline client fields (mode=New)
    #[Assert\NotBlank(groups: ['new_client'])]
    #[Assert\Length(max: 125, groups: ['new_client'])]
    #[UniqueClientName(groups: ['new_client'])]
    public ?string $newClientName = null;

    #[Assert\NotBlank(groups: ['new_client'])]
    #[Assert\Length(max: 125, groups: ['new_client'])]
    public ?string $newContactFirstName = null;

    #[Assert\Length(max: 125, groups: ['new_client'])]
    public ?string $newContactLastName = null;

    #[Assert\NotBlank(groups: ['new_client'])]
    #[Assert\Email(mode: Assert\Email::VALIDATION_MODE_STRICT, groups: ['new_client'])]
    public ?string $newContactEmail = null;

    // Quote entity fields
    #[Assert\NotBlank]
    public string $quoteId = '';

    #[Assert\Type(DateTimeInterface::class)]
    public ?DateTimeInterface $due = null;

    public ?Discount $discount = null;

    public ?string $terms = null;

    public ?string $notes = null;

    public ?string $total = '0';

    public ?string $baseTotal = '0';

    public ?string $tax = '0';

    /**
     * @var Collection<int, Line>
     */
    #[Assert\Valid]
    #[Assert\Count(min: 1)]
    public Collection $lines;

    /**
     * @var Collection<int, Contact>
     */
    #[Assert\Count(min: 1, groups: ['existing_client'])]
    public Collection $users;

    /**
     * @var Collection<int, InvoiceTax>
     */
    #[Assert\Valid]
    public Collection $invoiceTaxes;

    public function __construct()
    {
        $this->lines = new ArrayCollection();
        $this->users = new ArrayCollection();
        $this->invoiceTaxes = new ArrayCollection();
    }

    /**
     * Returns the resolved client (from existing client or null for new client mode)
     */
    public function getResolvedClient(): ?Client
    {
        return $this->clientMode === QuoteClientMode::Existing ? $this->client : null;
    }

    /**
     * Checks if all required inline client data is filled
     */
    public function hasInlineClientData(): bool
    {
        return $this->newClientName !== null
            && $this->newContactFirstName !== null
            && $this->newContactEmail !== null;
    }
}
