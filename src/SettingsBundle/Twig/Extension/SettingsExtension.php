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
use Doctrine\DBAL\Exception;
use JsonException;
use SolidInvoice\ClientBundle\Entity\Address;
use SolidInvoice\SettingsBundle\SystemConfig;
use Twig\Attribute\AsTwigFunction;
use function json_decode;

/**
 * @see \SolidInvoice\SettingsBundle\Tests\Twig\Extension\SettingsExtensionTest
 */
class SettingsExtension
{
    public function __construct(
        private readonly SystemConfig $config
    ) {
    }

    /**
     * @param array<string, string|null> $address
     */
    #[AsTwigFunction(name: 'address')]
    public function renderAddress(array $address): string
    {
        return (string) Address::fromArray($address);
    }

    /**
     * @param mixed|null $default
     *
     * @return mixed|null
     *
     * @throws JsonException
     */
    #[AsTwigFunction(name: 'setting')]
    public function getSetting(string $key, $default = null, bool $decode = false)
    {
        try {
            $setting = $this->config->get($key);
        } catch (Exception) {
            return $default;
        }

        if (null === $setting) {
            return $default;
        }

        if ($decode && $setting) {
            return json_decode($setting, true, 512, JSON_THROW_ON_ERROR);
        }

        return $setting;
    }
}
