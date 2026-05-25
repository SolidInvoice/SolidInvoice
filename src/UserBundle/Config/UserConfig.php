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

namespace SolidInvoice\UserBundle\Config;

use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Entity\UserSetting;
use SolidInvoice\UserBundle\Enum\UserSettingType;
use SolidInvoice\UserBundle\Repository\UserSettingRepositoryInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @see \SolidInvoice\UserBundle\Tests\Config\UserConfigTest
 */
readonly class UserConfig
{
    public function __construct(
        private UserSettingRepositoryInterface $repository,
        private Security $security,
    ) {
    }

    public function get(UserSettingType $key, ?User $user = null): ?string
    {
        $user ??= $this->security->getUser();
        /** @var User|null $user */

        if (! $user instanceof UserInterface) {
            return null;
        }

        return $this->repository->getSetting($user, $key)?->getValue();
    }

    public function set(UserSettingType $key, ?string $value, ?User $user = null): void
    {
        $user ??= $this->security->getUser();
        /** @var User|null $user */

        if (! $user instanceof UserInterface) {
            return;
        }

        $this->repository->saveSetting($user, $key, $value);
    }

    public function has(UserSettingType $key, ?User $user = null): bool
    {
        $user ??= $this->security->getUser();
        /** @var User|null $user */

        if (! $user instanceof UserInterface) {
            return false;
        }

        return $this->repository->getSetting($user, $key) instanceof UserSetting;
    }

    public function remove(UserSettingType $key, ?User $user = null): void
    {
        $user ??= $this->security->getUser();
        /** @var User|null $user */

        if (! $user instanceof UserInterface) {
            return;
        }

        $this->repository->removeSetting($user, $key);
    }

    /**
     * @return array<string, string|null>
     */
    public function getAll(?User $user = null): array
    {
        $user ??= $this->security->getUser();
        /** @var User|null $user */

        if (! $user instanceof UserInterface) {
            return [];
        }

        return $this->repository->getAllForUser($user);
    }
}
