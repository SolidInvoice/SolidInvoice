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
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatableInterface;
use function sprintf;
use function Symfony\Component\String\u;

/**
 * @phpstan-consistent-constructor
 */
abstract class Column
{
    private ?TranslatableInterface $label = null;

    private bool $sortable = true;

    private ?string $sortableField = null;

    private ?Closure $format = null;

    private ?ColumnFilterInterface $filter = null;

    private bool $searchable = true;

    private ?string $link = null;

    private ?string $linkRoute = null;

    /**
     * @var array<string, mixed>
     */
    private array $linkParameters = [];

    final public function __construct(
        protected string $field
    ) {
    }

    public static function new(string $field): static
    {
        return new static($field);
    }

    public function label(string | TranslatableInterface $label): static
    {
        $this->label = $label instanceof TranslatableInterface ? $label : new TranslatableMessage($label);

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

    public function searchable(bool $searchable): static
    {
        $this->searchable = $searchable;

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

    public function linkTo(string $url): static
    {
        if ($this->linkRoute !== null && $this->linkRoute !== '') {
            throw new \InvalidArgumentException(sprintf('Route link is already set for column %s. Either one of linkTo() or linkToRoute() must be used', $this->field));
        }

        $this->link = $url;

        return $this;
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function linkToRoute(string $routeName, array $parameters = []): static
    {
        if ($this->link !== null && $this->link !== '') {
            throw new \InvalidArgumentException(sprintf('Link is already set for column %s. Either one of linkTo() or linkToRoute() must be used', $this->field));
        }

        $this->linkRoute = $routeName;
        $this->linkParameters = $parameters;

        return $this;

    }

    /* ============================ GETTERS ============================ */

    public function getField(): string
    {
        return $this->field;
    }

    public function getLabel(): TranslatableInterface
    {
        return $this->label ?? new TranslatableMessage(u($this->field)->snake()->replace('_', ' ')->title(true)->toString());
    }

    public function isSortable(): bool
    {
        return $this->sortable;
    }

    public function isSearchable(): bool
    {
        return $this->searchable;
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

    public function getLink(): ?string
    {
        return $this->link;
    }

    public function getLinkRoute(): ?string
    {
        return $this->linkRoute;
    }

    /**
     * @return array<string, mixed>
     */
    public function getLinkParameters(): array
    {
        return $this->linkParameters;
    }
}
