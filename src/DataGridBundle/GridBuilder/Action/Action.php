<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\DataGridBundle\GridBuilder\Action;

class Action
{
    private string $route = '';

    /**
     * @var array<string, mixed>
     */
    private array $parameters = [];

    private string $icon = '';

    private string $label = '';

    final public function __construct()
    {
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public static function new(string $route, array $parameters = []): static
    {
        return new static();
    }

    public function icon(string $icon): static
    {
        $this->icon = $icon;
        return $this;
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function route(string $route, array $parameters = []): static
    {
        $this->route = $route;
        $this->parameters = $parameters;

        return $this;
    }

    public function label(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getRoute(): string
    {
        return $this->route;
    }

    /**
     * @return array<string, mixed>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getIcon(): string
    {
        return $this->icon;
    }

    public function getLabel(): string
    {
        return $this->label;
    }
}
