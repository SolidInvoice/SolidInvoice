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

namespace SolidInvoice\DataGridBundle;

use SolidInvoice\DataGridBundle\GridBuilder\Action\Action;
use SolidInvoice\DataGridBundle\GridBuilder\Column\Column;

abstract class Grid implements GridInterface
{
    /**
     * @return list<Column>
     */
    public function columns(): array
    {
        return [];
    }

    /**
     * @return list<Action>
     */
    public function actions(): array
    {
        return [];
    }

    public function batchActions(): array
    {
        return [];
    }

    public function filters(): iterable
    {
        foreach ($this->columns() as $column) {
            if (null !== $column->getFilter()) {
                yield $column->getField() => $column->getFilter();
            }
        }
    }
}
