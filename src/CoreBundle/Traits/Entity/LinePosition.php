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

use Doctrine\ORM\Mapping as ORM;
use SolidInvoice\CoreBundle\Entity\LineInterface;
use function max;

/**
 * The line's half of the position bookkeeping the owners do in {@see LinePositions}.
 */
trait LinePosition
{
    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;

        return $this;
    }

    /**
     * Places a line attached to its owner directly rather than through the owner's
     * `addLine()`, so {@see LineInterface::UNPLACED} is never stored — it is `PHP_INT_MAX`,
     * and the column is an `INTEGER`.
     *
     * The line lands after the siblings the owner has already placed, which is where
     * `addLine()` would have put it. Two such lines in one flush still collide: neither is in
     * the collection, so neither can see the other.
     */
    #[ORM\PrePersist]
    public function placeUnplacedLine(): void
    {
        if ($this->position !== LineInterface::UNPLACED) {
            return;
        }

        $this->position = 0;

        foreach ($this->siblingLines() as $sibling) {
            // An unplaced sibling has no slot to come after, and PHP_INT_MAX + 1 is a float.
            if ($sibling !== $this && $sibling->getPosition() !== LineInterface::UNPLACED) {
                $this->position = max($this->position, $sibling->getPosition() + 1);
            }
        }
    }

    /**
     * This line's owner's lines, or nothing while it has no owner.
     *
     * @return iterable<LineInterface>
     */
    abstract protected function siblingLines(): iterable;
}
