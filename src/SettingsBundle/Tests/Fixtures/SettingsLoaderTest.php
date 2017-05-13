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

namespace CSBill\SettingsBundle\Tests\Fixtures;

use CSBill\SettingsBundle\Loader\SettingsLoaderInterface;

class SettingsLoaderTest implements SettingsLoaderInterface
{
    private $settings = [];

    public function __construct(array $settings = [])
    {
        $this->settings = $settings;
    }

    public function getSettings(): array
    {
        return $this->settings;
    }

    public function saveSettings(array $settings = [])
    {
        $this->settings = $settings;
    }
}
