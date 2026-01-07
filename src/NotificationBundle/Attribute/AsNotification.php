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

namespace SolidInvoice\NotificationBundle\Attribute;

use Attribute;
use SolidInvoice\NotificationBundle\Enum\NotificationCategory;

#[Attribute(Attribute::TARGET_CLASS)]
final class AsNotification
{
    public function __construct(
        public string $name,
        public string $title = '',
        public string $description = '',
        public string $icon = 'tabler:bell',
        public NotificationCategory $category = NotificationCategory::OTHER,
    ) {
    }
}
