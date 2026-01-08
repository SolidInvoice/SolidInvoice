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

namespace SolidInvoice\SettingsBundle\Collection;

class ConfigCollection
{
    /**
     * @var array<string, array<string, mixed>>
     */
    protected $elements = [];

    /**
     * @var string|null
     */
    protected $current = null;

    /**
     * @var list<string>
     */
    protected $sections = [];

    /**
     * Start a new section.
     */
    public function startSection(string $sectionName): void
    {
        $this->current = $sectionName;

        $this->sections[] = $this->current;
    }

    /**
     * Adds config to the current section.
     * @param array<string, mixed> $settings
     */
    public function add(array $settings): void
    {
        $this->elements[$this->current] = $settings;
    }

    /**
     * Get the settings for the current section.
     * @return array<string, mixed>
     */
    public function getSettings(): array
    {
        return $this->elements[$this->current];
    }

    /**
     * Get the list of available sections.
     * @return list<string>
     */
    public function getSections(): array
    {
        return $this->sections;
    }

    /**
     * Ends the current section.
     */
    public function endSection(): void
    {
        $this->current = null;
    }
}
