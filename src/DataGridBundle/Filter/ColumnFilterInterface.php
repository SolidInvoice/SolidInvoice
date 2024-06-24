<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\DataGridBundle\Filter;

use Symfony\Component\Form\FormInterface;

interface ColumnFilterInterface extends FilterInterface
{
    /**
     * @return class-string<FormInterface>
     */
    public function form(): string;
}
