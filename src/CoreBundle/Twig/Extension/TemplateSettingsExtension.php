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

namespace SolidInvoice\CoreBundle\Twig\Extension;

use const JSON_THROW_ON_ERROR;
use SolidInvoice\ClientBundle\Entity\Address;
use SolidInvoice\SettingsBundle\SystemConfig;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use function array_fill_keys;
use function in_array;
use function is_string;
use function json_decode;

/**
 * Exposes a narrow subset of settings to billing templates rendered through the
 * sandboxed billing template resolver.
 *
 * Unlike the global `setting()` twig function this extension only allows a
 * caller-defined whitelist of keys. This prevents user-edited templates from
 * being able to read sensitive values such as SMTP passwords or API tokens.
 *
 * @see \SolidInvoice\CoreBundle\Tests\Twig\Extension\TemplateSettingsExtensionTest
 */
final class TemplateSettingsExtension extends AbstractExtension
{
    /**
     * Settings that are safe to expose to user-editable templates. Keep this
     * list narrow — anything added here is readable by any company member who
     * can edit a billing template.
     *
     * @var list<string>
     */
    public const ALLOWED_SETTINGS = [
        'system/company/company_name',
        'system/company/vat_number',
        'system/company/contact_details/address',
        'system/company/contact_details/email',
        'system/company/contact_details/phone_number',
        'system/company/logo',
        'system/company/currency',
        'system/general/hide_powered_by',
        'invoice/watermark',
        'quote/watermark',
    ];

    public function __construct(
        private readonly SystemConfig $config,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('template_setting', $this->getSetting(...)),
            new TwigFunction('template_address', $this->renderAddress(...)),
        ];
    }

    /**
     * @param mixed $default
     *
     * @return mixed
     */
    public function getSetting(string $key, $default = null, bool $decode = false)
    {
        if (! in_array($key, self::ALLOWED_SETTINGS, true)) {
            return $default;
        }

        $value = $this->config->get($key);

        if (null === $value) {
            return $default;
        }

        if ($decode && is_string($value) && '' !== $value) {
            return json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        }

        return $value;
    }

    /**
     * @param array<string, string|null>|null $address
     */
    public function renderAddress(?array $address): string
    {
        if (null === $address) {
            return '';
        }

        return (string) Address::fromArray($address);
    }

    /**
     * @return array<string, true>
     */
    public static function allowedSettingsLookup(): array
    {
        /** @var array<string, true> $lookup */
        $lookup = array_fill_keys(self::ALLOWED_SETTINGS, true);

        return $lookup;
    }
}
