<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\DataGridBundle\Attributes;

use Attribute;

#[Attribute]
final class AsDataGrid
{
    final public const DI_TAG = 'solidinvoice.data_grid.grid';

    public function __construct(
        public string $name,
        public ?string $title = null,
    ) {
    }
}
