<?php

/*
 * This file is part of the CSBillSettingsBundle package.
 *
 * (c) Pierre du Plessis <info@customscripts.co.za>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CSBill\SettingsBundle\Collection;

use Zend\Config\Config;

/**
 * Class ConfigCollection
 * @package CSBill\SettingsBundle\Collection
 */
class ConfigCollection
{
    /**
     * @var array
     */
    protected $elements = array();

    /**
     * @var string
     */
    protected $current;

    /**
     * @var array
     */
    protected $sections = array();

    /**
     * Start a new section
     *
     * @param string $sectionName
     */
    public function startSection($sectionName)
    {
        $this->current = $sectionName;

        $this->sections[] = $this->current;
    }

    /**
     * Adds config to the current section
     * @param Config $settings
     */
    public function add(Config $settings)
    {
        $this->elements[$this->current] = $settings;
    }

    /**
     * Get the settings for the current section
     *
     * @return Config
     */
    public function getSettings()
    {
        return $this->elements[$this->current];
    }

    /**
     * Get the list of available sections
     * @return array
     */
    public function getSections()
    {
        return $this->sections;
    }

    /**
     * Ends the current section
     */
    public function endSection()
    {
        $this->current = null;
    }
}
