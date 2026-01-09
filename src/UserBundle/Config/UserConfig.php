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
use SolidInvoice\UserBundle\Enum\UserSettingType;
use SolidInvoice\UserBundle\Repository\UserSettingRepositoryInterface;
use Symfony\Bundle\SecurityBundle\Security;

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

        if (null === $user) {
            return null;
        }

        return $this->repository->getSetting($user, $key)?->getValue();
    }

    public function set(UserSettingType $key, ?string $value, ?User $user = null): void
    {
        $user ??= $this->security->getUser();
        /** @var User|null $user */

        if (null === $user) {
            return;
        }

        $this->repository->saveSetting($user, $key, $value);
    }

    public function has(UserSettingType $key, ?User $user = null): bool
    {
        $user ??= $this->security->getUser();
        /** @var User|null $user */

        if (null === $user) {
            return false;
        }

        return null !== $this->repository->getSetting($user, $key);
    }

    public function remove(UserSettingType $key, ?User $user = null): void
    {
        $user ??= $this->security->getUser();
        /** @var User|null $user */

        if (null === $user) {
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

        if (null === $user) {
            return [];
        }

        return $this->repository->getAllForUser($user);
    }
}
