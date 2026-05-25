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

namespace SolidInvoice\DataGridBundle\GridBuilder\Column;

use Override;

/**
 * @see \SolidInvoice\DataGridBundle\Tests\GridBuilder\Column\MoneyColumnTest
 */
final class MoneyColumn extends Column
{
    #[Override]
    public static function new(string $field): static
    {
        return parent::new($field)
            ->cellClass('col-money');
    }
}
