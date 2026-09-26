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

namespace SolidInvoice\EInvoiceBundle\Listener;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use SolidInvoice\EInvoiceBundle\Entity\EInvoiceDocument;
use SolidInvoice\EInvoiceBundle\Exception\DocumentAlreadyClearedException;
use function array_intersect;
use function array_keys;

/**
 * Catches a write to one of {@see EInvoiceDocument::IMMUTABLE_FIELDS} that arrives through
 * hydration or reflection rather than the entity's own guarded setters. Both mechanisms are
 * required — see `design` §6 on SOL-89: the guarded setters alone make the immutability rule "by
 * convention", which #2676 explicitly rejects.
 *
 * Checked against the document's **current** marking, not the transition being applied: during
 * the `accept` transition the marking is still `transmitted` when `externalId` and `receipt` are
 * written, so that write must remain allowed.
 */
#[AsEntityListener(Events::preUpdate, entity: EInvoiceDocument::class)]
final readonly class ImmutablePayloadListener
{
    public function preUpdate(EInvoiceDocument $document, PreUpdateEventArgs $event): void
    {
        if (! $document->getStatus()->isTerminal()) {
            return;
        }

        if (array_intersect(EInvoiceDocument::IMMUTABLE_FIELDS, array_keys($event->getEntityChangeSet())) !== []) {
            throw new DocumentAlreadyClearedException($document->getId());
        }
    }
}
