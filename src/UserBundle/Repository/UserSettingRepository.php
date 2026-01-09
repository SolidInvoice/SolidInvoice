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

use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Entity\UserSetting;
use SolidInvoice\UserBundle\Enum\UserSettingType;
use SolidWorx\Platform\PlatformBundle\Repository\EntityRepository;

/**
 * @extends EntityRepository<UserSetting>
 */
final class UserSettingRepository extends EntityRepository implements UserSettingRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserSetting::class);
    }

    public function getSetting(User $user, UserSettingType $key): ?UserSetting
    {
        return $this->findOneBy([
            'user' => $user,
            'key' => $key,
        ]);
    }

    public function saveSetting(User $user, UserSettingType $key, ?string $value): void
    {
        $setting = $this->getSetting($user, $key);

        if (null === $setting) {
            $setting = new UserSetting();
            $setting->setUser($user);
            $setting->setKey($key);
        }

        $setting->setValue($value);

        $this->getEntityManager()->persist($setting);
        $this->getEntityManager()->flush();
    }

    public function removeSetting(User $user, UserSettingType $key): void
    {
        $setting = $this->getSetting($user, $key);

        if (null !== $setting) {
            $this->getEntityManager()->remove($setting);
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return array<string, string|null>
     */
    public function getAllForUser(User $user): array
    {
        $settings = $this->findBy(['user' => $user]);

        $result = [];
        foreach ($settings as $setting) {
            $result[$setting->getKey()->value] = $setting->getValue();
        }

        return $result;
    }
}
