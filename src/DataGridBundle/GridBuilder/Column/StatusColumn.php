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

namespace SolidInvoice\DataGridBundle\GridBuilder\Column;

/**
 * StatusColumn renders values as styled status badges.
 *
 * Usage:
 *   StatusColumn::new('status')
 *       ->statusMap([
 *           'paid' => 'success',
 *           'pending' => 'warning',
 *           'draft' => 'secondary',
 *           'cancelled' => 'danger',
 *       ])
 *
 * Or use a single variant for all values:
 *   StatusColumn::new('status')->variant('success')
 */
final class StatusColumn extends Column
{
    /**
     * Maps status values to badge variants (success, danger, warning, info, primary, secondary).
     *
     * @var array<string, string>
     */
    private array $statusMap = [];

    /**
     * Default variant to use when status is not in the map.
     */
    private string $defaultVariant = 'secondary';

    /**
     * If true, transform the status value to title case for display.
     */
    private bool $titleCase = true;

    /**
     * Set the mapping of status values to badge variants.
     *
     * @param array<string, string> $map Map of status value => variant name
     */
    public function statusMap(array $map): self
    {
        $this->statusMap = $map;
        return $this;
    }

    /**
     * Set a single variant for all status values.
     * This is a convenience method when all statuses should have the same style.
     */
    public function variant(string $variant): self
    {
        $this->defaultVariant = $variant;
        return $this;
    }

    /**
     * Set whether to transform status values to title case for display.
     */
    public function titleCase(bool $titleCase = true): self
    {
        $this->titleCase = $titleCase;
        return $this;
    }

    /**
     * @return array<string, string>
     */
    public function getStatusMap(): array
    {
        return $this->statusMap;
    }

    public function getDefaultVariant(): string
    {
        return $this->defaultVariant;
    }

    public function isTitleCase(): bool
    {
        return $this->titleCase;
    }

    /**
     * Get the variant for a given status value.
     */
    public function getVariantForStatus(string $status): string
    {
        return $this->statusMap[strtolower($status)] ?? $this->defaultVariant;
    }
}
