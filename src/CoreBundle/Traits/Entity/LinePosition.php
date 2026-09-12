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

use Doctrine\Common\Collections\Collection;
use SolidInvoice\CoreBundle\Entity\LineInterface;
use function max;

/**
 * The line's half of the position bookkeeping the owners do in {@see LinePositions}.
 */
trait LinePosition
{
    /**
     * The slot after the last one the owner has placed — where an append would have put the
     * line.
     *
     * The first slot is the wrong fallback for a line nobody placed: it is the slot the
     * collection's first line already holds, so a line falling back to it lands ahead of
     * lines that were placed deliberately, and a second one duplicates it.
     *
     * @template TLine of LineInterface
     *
     * @param Collection<int, TLine> $siblings
     */
    protected function positionAfter(Collection $siblings): int
    {
        $position = 0;

        foreach ($siblings as $sibling) {
            // An unplaced sibling has no slot to come after, and PHP_INT_MAX + 1 is a float.
            if ($sibling === $this || $sibling->getPosition() === LineInterface::UNPLACED) {
                continue;
            }

            $position = max($position, $sibling->getPosition() + 1);
        }

        return $position;
    }
}
