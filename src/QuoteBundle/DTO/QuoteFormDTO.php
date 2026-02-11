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
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\QuoteBundle\Entity\Line;
use SolidInvoice\QuoteBundle\Enum\QuoteClientMode;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO for Quote form data
 */
final class QuoteFormDTO
{
    public QuoteClientMode $clientMode;

    // Existing client selection (mode=Existing)
    #[Assert\NotBlank(groups: ['existing_client'])]
    public ?Client $client = null;

    // Inline client fields (mode=New)
    #[Assert\NotBlank(groups: ['new_client'])]
    #[Assert\Length(max: 125, groups: ['new_client'])]
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

    public ?string $total = null;

    public ?string $baseTotal = null;

    public ?string $tax = null;

    /**
     * @var ArrayCollection<int, Line>
     */
    #[Assert\Valid]
    #[Assert\Count(min: 1)]
    public ArrayCollection $lines;

    /**
     * @var ArrayCollection<int, Contact>
     */
    #[Assert\Count(min: 1, groups: ['existing_client'])]
    public ArrayCollection $users;

    public function __construct()
    {
        $this->clientMode = QuoteClientMode::Existing;
        $this->lines = new ArrayCollection();
        $this->users = new ArrayCollection();
        $this->total = '0';
        $this->baseTotal = '0';
        $this->tax = '0';
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
