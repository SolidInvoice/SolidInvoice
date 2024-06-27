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

use Closure;
use SolidInvoice\DataGridBundle\Filter\ColumnFilterInterface;
use function Symfony\Component\String\u;

/**
 * @phpstan-consistent-constructor
 */
abstract class Column
{
    private ?string $label = null;

    private bool $sortable = true;

    private ?string $sortableField = null;

    private ?Closure $format = null;

    private ?ColumnFilterInterface $filter = null;

    final public function __construct(
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

    public function filter(ColumnFilterInterface $filter): static
    {
        $this->filter = $filter;

        return $this;
    }

    public function formatValue(Closure $format): static
    {
        $this->format = $format;
        return $this;
    }

    public function sortableField(string $string): static
    {
        $this->sortableField = $string;

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

    public function getFilter(): ?ColumnFilterInterface
    {
        return $this->filter;
    }

    public function getFormatValue(): Closure
    {
        return $this->format ?? static fn (mixed $value): mixed => $value;
    }

    public function getSortableField(): string
    {
        return $this->sortableField ?? $this->getField();
    }
}
