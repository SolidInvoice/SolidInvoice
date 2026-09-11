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
 */
trait LinePositions
{
    /**
     * Renumbers the lines to `0..n-1`, keeping their current relative order, and places any
     * line that has no position yet at the end.
     *
     * This is the only position primitive the owners need: an added line arrives
     * {@see LineInterface::UNPLACED}, sorts last, and so becomes an append; a removed line
     * leaves a gap that the same pass closes.
     *
     * Positions are compacted eagerly rather than left sparse. Sparse ordering would make an
     * insert cheaper, but an invoice has a handful of lines, and contiguous positions mean the
     * stored value is the same number the reader sees — so a reorder is a plain "set these n
     * values", with no gap to run out of and no rebalancing pass.
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
