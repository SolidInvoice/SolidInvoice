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

namespace SolidInvoice\DashboardBundle\Checklist\Items;

use SolidInvoice\DashboardBundle\Checklist\ChecklistItemInterface;
use SolidInvoice\SettingsBundle\SystemConfig;

final readonly class CustomizeSettingsItem implements ChecklistItemInterface
{
    public function __construct(
        private SystemConfig $systemConfig,
    ) {
    }

    public function getName(): string
    {
        return 'dashboard.checklist.customize_settings.name';
    }

    public function getDescription(): string
    {
        return 'dashboard.checklist.customize_settings.description';
    }

    public function getIcon(): string
    {
        return 'tabler:settings';
    }

    public function getRoute(): string
    {
        return '_settings';
    }

    public function getPriority(): int
    {
        return -200;
    }

    public function active(): bool
    {
        return false;
    }

    public function isComplete(): bool
    {
        // Check if at least 2 of the key company settings have been configured
        $settingsToCheck = [
            'system/company/contact_details/address',
            'system/company/contact_details/phone_number',
            'system/company/contact_details/email',
            'system/company/vat_number',
        ];

        $configuredCount = 0;
        foreach ($settingsToCheck as $setting) {
            $value = $this->systemConfig->get($setting);
            if (null !== $value && '' !== $value) {
                ++$configuredCount;
            }
        }

        return $configuredCount >= 2;
    }
}
