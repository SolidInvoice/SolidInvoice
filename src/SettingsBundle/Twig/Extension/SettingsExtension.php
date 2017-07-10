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

namespace CSBill\SettingsBundle\Twig\Extension;

use CSBill\SettingsBundle\Exception\InvalidSettingException;
use CSBill\SettingsBundle\SystemConfig;
use Doctrine\DBAL\Exception\TableNotFoundException;

class SettingsExtension extends \Twig_Extension
{
    /**
     * @var SystemConfig
     */
    private $config;

    public function __construct(SystemConfig $config)
    {
        $this->config = $config;
    }

    public function getFunctions()
    {
        return [
            new \Twig_Function('setting', [$this, 'getSetting'])
        ];
    }

    public function getSetting(string $setting, $default = null)
    {
        try {
            return $this->config->get($setting);
        } catch (InvalidSettingException | TableNotFoundException $e) {
            return $default;
        }
    }
}