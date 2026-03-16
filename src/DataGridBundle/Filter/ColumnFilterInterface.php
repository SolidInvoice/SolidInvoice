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

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Form\FormTypeInterface;

interface ColumnFilterInterface extends FilterInterface
{
    /**
     * @return class-string<FormTypeInterface>
     */
    public function form(): string;

    /**
     * @return array<string, mixed>
     */
    public function formOptions(): array;

    /**
     * Return a human-readable label for a stored filter value (used in active-filter chips).
     */
    public function getDisplayValue(mixed $value, ManagerRegistry $registry): string;
}
