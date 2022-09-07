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

namespace SolidInvoice\SettingsBundle\Twig\Extension;

use const JSON_THROW_ON_ERROR;
use SolidInvoice\ClientBundle\Entity\Address;
use SolidInvoice\SettingsBundle\SystemConfig;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use function json_decode;

/**
 * @see \SolidInvoice\SettingsBundle\Tests\Twig\Extension\SettingsExtensionTest
 */
class SettingsExtension extends AbstractExtension
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
            new TwigFunction('setting', function (string $setting, $default = null, $decode = false) {
                return $this->getSetting($setting, $default, $decode);
            }),
            new TwigFunction('address', function (array $address) {
                return $this->renderAddress($address);
            }),
        ];
    }

    public function renderAddress(array $address)
    {
        return (string) Address::fromArray($address);
    }

    public function getSetting(string $key, $default = null, $decode = false)
    {
        $setting = $this->config->get($key);

        if (null === $setting) {
            return $default;
        }

        if ($decode && $setting) {
            return json_decode($setting, true, 512, JSON_THROW_ON_ERROR);
        }

        return $setting;
    }
}
