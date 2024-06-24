<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\DataGridBundle\GridBuilder\Batch;

use Closure;

class BatchAction
{
    protected bool $confirm = true;

    protected ?Closure $action = null;

    protected string $route = '';

    /**
     * @var array<string, mixed>
     */
    protected array $routeParameters = [];

    protected string $label = '';

    protected string $icon = '';

    protected string $color = '';

    final public function __construct()
    {
    }

    public static function new(string $label): static
    {
        return (new static())
            ->label($label);
    }

    public function confirm(bool $confirm = true): static
    {
        $this->confirm = $confirm;

        return $this;
    }

    public function action(callable $action): static
    {
        $this->action = $action(...);

        return $this;
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function route(string $route, array $parameters = []): static
    {
        $this->route = $route;
        $this->routeParameters = $parameters;

        return $this;
    }

    public function label(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function icon(string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    public function color(string $color): static
    {
        $this->color = $color;

        return $this;
    }

    /* ==================== GETTERS ==================== */

    public function getAction(): ?Closure
    {
        return $this->action;
    }

    public function getRoute(): string
    {
        return $this->route;
    }

    /**
     * @return array<string, mixed>
     */
    public function getRouteParameters(): array
    {
        return $this->routeParameters;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getIcon(): string
    {
        return $this->icon;
    }

    public function getColor(): string
    {
        return $this->color;
    }
}
