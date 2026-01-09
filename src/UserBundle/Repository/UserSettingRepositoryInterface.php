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

namespace SolidInvoice\UserBundle\Repository;

use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Entity\UserSetting;
use SolidInvoice\UserBundle\Enum\UserSettingType;

interface UserSettingRepositoryInterface
{
    public function getSetting(User $user, UserSettingType $key): ?UserSetting;

    public function saveSetting(User $user, UserSettingType $key, ?string $value): void;

    public function removeSetting(User $user, UserSettingType $key): void;

    /**
     * @return array<string, string|null>
     */
    public function getAllForUser(User $user): array;
}
