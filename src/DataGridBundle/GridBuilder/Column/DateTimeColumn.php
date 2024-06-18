<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\DataGridBundle\GridBuilder\Column;

class DateTimeColumn extends Column
{
    private string $format = 'Y-m-d H:i:s';

    public function format(string $format): self
    {
        $this->format = $format;
        return $this;
    }

    public function getFormat(): string
    {
        return $this->format;
    }
}
