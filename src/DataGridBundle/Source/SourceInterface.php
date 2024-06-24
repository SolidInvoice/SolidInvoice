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

namespace SolidInvoice\DataGridBundle\Source;

use Doctrine\ORM\QueryBuilder;
use SolidInvoice\DataGridBundle\GridInterface;

interface SourceInterface
{
    public function fetch(GridInterface $grid): QueryBuilder;
}
