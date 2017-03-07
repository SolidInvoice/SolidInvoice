<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\SettingsBundle\Collection;

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
     *
     * @param string $sectionName
     */
    public function startSection($sectionName)
    {
	$this->current = $sectionName;

	$this->sections[] = $this->current;
    }

    /**
     * Adds config to the current section.
     *
     * @param array $settings
     */
    public function add(array $settings)
    {
	$this->elements[$this->current] = $settings;
    }

    /**
     * Get the settings for the current section.
     *
     * @return array
     */
    public function getSettings()
    {
	return $this->elements[$this->current];
    }

    /**
     * Get the list of available sections.
     *
     * @return array
     */
    public function getSections()
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
