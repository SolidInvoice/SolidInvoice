<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\SettingsBundle\Collection;

/**
 * Class ConfigCollection.
 */
class ConfigCollection
{
    /**
     * @var array
     */
    protected $elements = [];

    /**
     * @var string
     */
    protected $current;

    /**
     * @var array
     */
    protected $sections = [];

    /**
     * Start a new section.
     */
    public function startSection(string $sectionName)
    {
        $this->current = $sectionName;

        $this->sections[] = $this->current;
    }

    /**
     * Adds config to the current section.
     */
    public function add(array $settings)
    {
        $this->elements[$this->current] = $settings;
    }

    /**
     * Get the settings for the current section.
     */
    public function getSettings(): array
    {
        return $this->elements[$this->current];
    }

    /**
     * Get the list of available sections.
     */
    public function getSections(): array
    {
        return $this->sections;
    }

    /**
     * Ends the current section.
     */
    public function endSection()
    {
        $this->current = null;
    }
}
