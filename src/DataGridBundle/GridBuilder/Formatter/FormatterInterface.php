<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\DataGridBundle\GridBuilder\Formatter;

use SolidInvoice\DataGridBundle\GridBuilder\Column\Column;

interface FormatterInterface
{
    public function format(Column $column, mixed $value): string;
}
