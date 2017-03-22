<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\SettingsBundle\Manager;

use CSBill\SettingsBundle\Loader\SettingsLoaderInterface;

interface ManagerInterface
{
    /**
     * @param SettingsLoaderInterface $loader
     *
     * @return SettingsLoaderInterface|void
     */
    public function addSettingsLoader(SettingsLoaderInterface $loader);

    /**
     * @param string|null $setting
     *
     * @return \Doctrine\Common\Collections\Collection|mixed|string
     *
     * @throws \CSBill\SettingsBundle\Exception\InvalidSettingException
     */
    public function get(string $setting = null);

    /**
     * Recursively set settings from an array.
     *
     * @param array $settings
     *
     * @return mixed|void
     */
    public function set(array $settings = []);

    /**
     * @return array
     */
    public function getSettings(): array;
}
