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

namespace SolidInvoice\CoreBundle\Traits\Entity;

use SolidInvoice\CoreBundle\Entity\LineInterface;
use function usort;

/**
 * Position bookkeeping for the owner of a line collection — {@see \SolidInvoice\InvoiceBundle\Entity\Invoice},
 * {@see \SolidInvoice\InvoiceBundle\Entity\RecurringInvoice} and {@see \SolidInvoice\QuoteBundle\Entity\Quote}.
 *
 * Hand-rolled rather than Gedmo's Sortable, which is installed and otherwise fits. Its ORM
 * adapter binds the sortable group with `$metadata->getTypeOfField($group)` — null for an
 * association — so a ULID owner binds untyped and matches no row. `getMaxPosition()` reads
 * that as "no rows" and returns -1, so every reorder silently clamps to position 0. Revisit
 * if that binding is fixed upstream.
 */
trait LinePositions
{
    /**
     * Renumbers the lines to `0..n-1`, keeping their relative order.
     *
     * The only position primitive the owners need: an added line arrives
     * {@see LineInterface::UNPLACED}, sorts last and so becomes an append; a removed line
     * leaves a gap this same pass closes. Positions stay contiguous rather than sparse —
     * an invoice has a handful of lines, so there is no gap to run out of and nothing to
     * rebalance.
     *
     * @param iterable<LineInterface> $lines
     */
    private function compactLinePositions(iterable $lines): void
    {
        $sorted = [];

        foreach ($lines as $line) {
            $sorted[] = $line;
        }

        // usort is stable, so lines that share a position — several unplaced ones, or a
        // hand-set duplicate — keep the order the collection already had.
        usort(
            $sorted,
            static fn (LineInterface $a, LineInterface $b): int => $a->getPosition() <=> $b->getPosition()
        );

        foreach ($sorted as $position => $line) {
            $line->setPosition($position);
        }
    }
}
