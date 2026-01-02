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

    /**
     * Primary actions show as icon buttons in the row.
     * Non-primary actions appear in the three-dot dropdown menu.
     */
    private bool $primary = true;

    private bool $confirm = false;

    private string $confirmMessage = 'Are you sure?';

    private string $color = '';

    final public function __construct()
    {
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public static function new(string $route, array $parameters = []): static
    {
        return (new static())
            ->route($route, $parameters);
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

    /**
     * Mark action as non-primary to show it in the dropdown menu instead of as an icon.
     */
    public function inMenu(): static
    {
        $this->primary = false;

        return $this;
    }

    /**
     * Require confirmation before executing the action.
     */
    public function confirm(string $message = 'Are you sure?'): static
    {
        $this->confirm = true;
        $this->confirmMessage = $message;

        return $this;
    }

    /**
     * Set the color/variant for the action (e.g., 'danger' for destructive actions).
     */
    public function color(string $color): static
    {
        $this->color = $color;

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

    public function isPrimary(): bool
    {
        return $this->primary;
    }

    public function shouldConfirm(): bool
    {
        return $this->confirm;
    }

    public function getConfirmMessage(): string
    {
        return $this->confirmMessage;
    }

    public function getColor(): string
    {
        return $this->color;
    }
}
