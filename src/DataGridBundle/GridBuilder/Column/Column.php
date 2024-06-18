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

use function Symfony\Component\String\u;

abstract class Column
{
    private ?string $label = null;

    private bool $sortable = true;

    public function __construct(
        protected string $field
    ) {
    }

    public static function new(string $field): static
    {
        return new static($field);
    }

    public function label(string $label): static
    {
        $this->label = $label;
        return $this;
    }

    public function sortable(bool $sortable = true): static
    {
        $this->sortable = $sortable;
        return $this;
    }

    /* ============================ GETTERS ============================ */

    public function getField(): string
    {
        return $this->field;
    }

    public function getLabel(): string
    {
        return $this->label ?? u($this->field)->snake()->replace('_', ' ')->title(true)->toString();
    }

    public function isSortable(): bool
    {
        return $this->sortable;
    }
}
