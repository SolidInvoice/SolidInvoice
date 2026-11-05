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
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use Doctrine\ORM\Mapping as ORM;
use Override;
use SolidInvoice\ApiBundle\State\Processor\CreditNoteLinePersistProcessor;
use SolidInvoice\CoreBundle\Entity\LineInterface;
use SolidInvoice\InvoiceBundle\Repository\CreditNoteLineRepository;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

/**
 * A credit note's line item. Extends {@see Line} rather than declaring its own table — `Line`
 * is `SINGLE_TABLE` inheritance on `invoice_lines`, and this follows the same shape as
 * {@see RecurringInvoiceLine}. See the corrected `CreditNoteLine` section of the `design`
 * document on SOL-69 for why a separate `credit_note_lines` table was rejected.
 *
 * The `#[ORM\Entity]` attribute is required even though nothing else about the mapping is
 * redeclared: Doctrine's attribute driver does not recognise a plain PHP subclass listed only
 * in the parent's `DiscriminatorMap` as an entity, mirroring {@see RecurringInvoiceLine}.
 *
 * `#[ApiResource]` is required here too, for an unrelated reason: without it, API Platform's
 * `ResourceClassResolver` has no registration more specific than `Line` for this subclass, so
 * every nested `CreditNoteLine` — both denormalizing the array {@see CreditNote::$lines} posts
 * and generating each line's own `@id` — resolves to `Line`'s resource metadata instead of this
 * class's, and 400s or 500s depending on which direction crosses the mismatch. Mirrors how
 * {@see RecurringInvoiceLine} solves the identical problem.
 */
#[ORM\Entity(repositoryClass: CreditNoteLineRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/credit-notes/{creditNoteId}/lines',
            uriVariables: [
                'creditNoteId' => new Link(
                    fromProperty: 'lines',
                    fromClass: CreditNote::class,
                ),
            ],
        ),
        new Post(
            uriTemplate: '/credit-notes/{creditNoteId}/lines',
            uriVariables: [
                'creditNoteId' => new Link(
                    fromProperty: 'lines',
                    fromClass: CreditNote::class,
                ),
            ],
            // The `lines` link would otherwise have API Platform deserialize into the owner's
            // existing line, so a second post overwrites the first. A create has nothing to read.
            read: false,
            processor: CreditNoteLinePersistProcessor::class,
        ),
        new Get(
            uriTemplate: '/credit-notes/{creditNoteId}/line/{id}',
            uriVariables: [
                'creditNoteId' => new Link(
                    fromProperty: 'lines',
                    fromClass: CreditNote::class,
                ),
                'id' => new Link(
                    fromClass: CreditNoteLine::class,
                ),
            ],
        ),
        new Patch(
            uriTemplate: '/credit-notes/{creditNoteId}/line/{id}',
            uriVariables: [
                'creditNoteId' => new Link(
                    fromProperty: 'lines',
                    fromClass: CreditNote::class,
                ),
                'id' => new Link(
                    fromClass: CreditNoteLine::class,
                ),
            ],
        ),
        new Delete(
            uriTemplate: '/credit-notes/{creditNoteId}/line/{id}',
            uriVariables: [
                'creditNoteId' => new Link(
                    fromProperty: 'lines',
                    fromClass: CreditNote::class,
                ),
                'id' => new Link(
                    fromClass: CreditNoteLine::class,
                ),
            ],
        ),
    ],
    normalizationContext: [
        AbstractObjectNormalizer::SKIP_NULL_VALUES => false,
    ],
    denormalizationContext: [
        AbstractObjectNormalizer::SKIP_NULL_VALUES => false,
    ]
)]
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
