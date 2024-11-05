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

use DateTime;
use DateTimeInterface;
use SolidInvoice\DataGridBundle\GridBuilder\Column\Column;
use SolidInvoice\DataGridBundle\GridBuilder\Column\DateTimeColumn;
use Symfony\Component\Translation\TranslatableMessage;

class DateTimeFormatter implements FormatterInterface
{
    public function format(Column $column, mixed $value): string|TranslatableMessage
    {
        if (null === $value) {
            return '';
        }

        assert($column instanceof DateTimeColumn);

        if (! $value instanceof DateTimeInterface) {
            $value = new DateTime($value);
        }

        return $value->format($column->getFormat());
    }
}
