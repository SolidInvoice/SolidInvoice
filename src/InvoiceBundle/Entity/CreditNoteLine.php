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

use Doctrine\ORM\Mapping as ORM;
use Override;
use SolidInvoice\CoreBundle\Entity\LineInterface;
use SolidInvoice\InvoiceBundle\Repository\CreditNoteLineRepository;

/**
 * A credit note's line item. Extends {@see Line} rather than declaring its own table — `Line`
 * is `SINGLE_TABLE` inheritance on `invoice_lines`, and this follows the same shape as
 * {@see RecurringInvoiceLine}. See the corrected `CreditNoteLine` section of the `design`
 * document on SOL-69 for why a separate `credit_note_lines` table was rejected.
 *
 * The `#[ORM\Entity]` attribute is required even though nothing else about the mapping is
 * redeclared: Doctrine's attribute driver does not recognise a plain PHP subclass listed only
 * in the parent's `DiscriminatorMap` as an entity, mirroring {@see RecurringInvoiceLine}.
 */
#[ORM\Entity(repositoryClass: CreditNoteLineRepository::class)]
class CreditNoteLine extends Line
{
    #[ORM\ManyToOne(targetEntity: CreditNote::class, inversedBy: 'lines')]
    #[ORM\JoinColumn(name: 'credit_note_id', nullable: true, onDelete: 'CASCADE')]
    private ?CreditNote $creditNote = null;

    public function setCreditNote(?CreditNote $creditNote): self
    {
        $this->creditNote = $creditNote;

        return $this;
    }

    public function getCreditNote(): ?CreditNote
    {
        return $this->creditNote;
    }

    /**
     * The owner is the credit note, not the invoice the parent looks at.
     *
     * @return iterable<LineInterface>
     */
    #[Override]
    protected function siblingLines(): iterable
    {
        if (! $this->creditNote instanceof CreditNote) {
            return [];
        }

        return $this->creditNote->getLines();
    }

    /**
     * No `#[ORM\PreRemove]` of its own: Doctrine inherits the parent's callbacks by method
     * name, and the name it already has resolves here.
     */
    #[Override]
    public function detachFromOwner(): void
    {
        $this->creditNote?->removeLine($this);
    }
}
