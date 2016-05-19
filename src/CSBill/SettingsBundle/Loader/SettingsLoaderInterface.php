<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\SettingsBundle\Loader;

/**
 * Interface SettingsLoaderInterface.
 */
interface SettingsLoaderInterface
{
    /**
     * Return an array with available settings.
     *
     * @return array
     */
    public function getSettings();

    /**
     * @param array $settings
     */
    public function saveSettings(array $settings = array());
}
