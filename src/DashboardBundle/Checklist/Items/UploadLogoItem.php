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

final readonly class UploadLogoItem implements ChecklistItemInterface
{
    public function __construct(
        private SystemConfig $systemConfig,
    ) {
    }

    public function getName(): string
    {
        return 'dashboard.checklist.upload_logo.name';
    }

    public function getDescription(): string
    {
        return 'dashboard.checklist.upload_logo.description';
    }

    public function getIcon(): string
    {
        return 'tabler:photo-up';
    }

    public function getRoute(): string
    {
        return '_settings';
    }

    public function getPriority(): int
    {
        return -600;
    }

    public function active(): bool
    {
        return true;
    }

    public function isComplete(): bool
    {
        $logo = $this->systemConfig->get('system/company/logo');

        return null !== $logo && '' !== $logo;
    }
}
