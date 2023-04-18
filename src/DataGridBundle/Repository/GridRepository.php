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

namespace SolidInvoice\DataGridBundle\Repository;

use SolidInvoice\DataGridBundle\Exception\InvalidGridException;
use SolidInvoice\DataGridBundle\GridInterface;

class GridRepository
{
    /**
     * @var GridInterface[]
     */
    private array $grids = [];

    public function addGrid(string $name, GridInterface $grid): void
    {
        $this->grids[$name] = $grid;
    }

    /**
     * @throws InvalidGridException
     */
    public function find(string $name): GridInterface
    {
        if (! array_key_exists($name, $this->grids)) {
            throw new InvalidGridException($name);
        }

        return $this->grids[$name];
    }
}
