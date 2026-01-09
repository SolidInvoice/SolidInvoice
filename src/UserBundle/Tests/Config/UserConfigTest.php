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

namespace SolidInvoice\UserBundle\Tests\Config;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use SolidInvoice\UserBundle\Config\UserConfig;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Entity\UserSetting;
use SolidInvoice\UserBundle\Enum\UserSettingType;
use SolidInvoice\UserBundle\Repository\UserSettingRepositoryInterface;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * @covers \SolidInvoice\UserBundle\Config\UserConfig
 */
final class UserConfigTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private m\MockInterface&UserSettingRepositoryInterface $repository;

    private m\MockInterface&Security $security;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = m::mock(UserSettingRepositoryInterface::class);
        $this->security = m::mock(Security::class);
        $this->user = new User();
        $this->user->setEmail('test@example.com');
    }

    public function testGetWithCurrentUser(): void
    {
        $setting = $this->createSetting(UserSettingType::Timezone, 'Europe/London');

        $this->security->shouldReceive('getUser')->andReturn($this->user);
        $this->repository->shouldReceive('getSetting')
            ->with($this->user, UserSettingType::Timezone)
            ->andReturn($setting);

        $config = new UserConfig($this->repository, $this->security);

        self::assertSame('Europe/London', $config->get(UserSettingType::Timezone));
    }

    public function testGetWithSpecificUser(): void
    {
        $specificUser = new User();
        $specificUser->setEmail('other@example.com');

        $setting = $this->createSetting(UserSettingType::Timezone, 'America/New_York');

        $this->repository->shouldReceive('getSetting')
            ->with($specificUser, UserSettingType::Timezone)
            ->andReturn($setting);

        $config = new UserConfig($this->repository, $this->security);

        self::assertSame('America/New_York', $config->get(UserSettingType::Timezone, $specificUser));
    }

    public function testGetReturnsNullWhenSettingDoesNotExist(): void
    {
        $this->security->shouldReceive('getUser')->andReturn($this->user);
        $this->repository->shouldReceive('getSetting')
            ->with($this->user, UserSettingType::Timezone)
            ->andReturn(null);

        $config = new UserConfig($this->repository, $this->security);

        self::assertNull($config->get(UserSettingType::Timezone));
    }

    public function testGetReturnsNullWhenNoUserLoggedIn(): void
    {
        $this->security->shouldReceive('getUser')->andReturn(null);

        $config = new UserConfig($this->repository, $this->security);

        self::assertNull($config->get(UserSettingType::Timezone));
    }

    public function testSetWithCurrentUser(): void
    {
        $this->security->shouldReceive('getUser')->andReturn($this->user);
        $this->repository->shouldReceive('saveSetting')
            ->once()
            ->with($this->user, UserSettingType::Timezone, 'UTC');

        $config = new UserConfig($this->repository, $this->security);
        $config->set(UserSettingType::Timezone, 'UTC');
    }

    public function testSetWithSpecificUser(): void
    {
        $specificUser = new User();
        $specificUser->setEmail('other@example.com');

        $this->repository->shouldReceive('saveSetting')
            ->once()
            ->with($specificUser, UserSettingType::Location, 'London');

        $config = new UserConfig($this->repository, $this->security);
        $config->set(UserSettingType::Location, 'London', $specificUser);
    }

    public function testSetDoesNothingWhenNoUserLoggedIn(): void
    {
        $this->security->shouldReceive('getUser')->andReturn(null);
        $this->repository->shouldNotReceive('saveSetting');

        $config = new UserConfig($this->repository, $this->security);
        $config->set(UserSettingType::Timezone, 'UTC');
    }

    public function testHasReturnsTrueWhenSettingExists(): void
    {
        $setting = $this->createSetting(UserSettingType::OnboardComplete, '1');

        $this->security->shouldReceive('getUser')->andReturn($this->user);
        $this->repository->shouldReceive('getSetting')
            ->with($this->user, UserSettingType::OnboardComplete)
            ->andReturn($setting);

        $config = new UserConfig($this->repository, $this->security);

        self::assertTrue($config->has(UserSettingType::OnboardComplete));
    }

    public function testHasReturnsFalseWhenSettingDoesNotExist(): void
    {
        $this->security->shouldReceive('getUser')->andReturn($this->user);
        $this->repository->shouldReceive('getSetting')
            ->with($this->user, UserSettingType::OnboardComplete)
            ->andReturn(null);

        $config = new UserConfig($this->repository, $this->security);

        self::assertFalse($config->has(UserSettingType::OnboardComplete));
    }

    public function testHasReturnsFalseWhenNoUserLoggedIn(): void
    {
        $this->security->shouldReceive('getUser')->andReturn(null);

        $config = new UserConfig($this->repository, $this->security);

        self::assertFalse($config->has(UserSettingType::OnboardComplete));
    }

    public function testRemoveWithCurrentUser(): void
    {
        $this->security->shouldReceive('getUser')->andReturn($this->user);
        $this->repository->shouldReceive('removeSetting')
            ->once()
            ->with($this->user, UserSettingType::Timezone);

        $config = new UserConfig($this->repository, $this->security);
        $config->remove(UserSettingType::Timezone);
    }

    public function testRemoveWithSpecificUser(): void
    {
        $specificUser = new User();
        $specificUser->setEmail('other@example.com');

        $this->repository->shouldReceive('removeSetting')
            ->once()
            ->with($specificUser, UserSettingType::Timezone);

        $config = new UserConfig($this->repository, $this->security);
        $config->remove(UserSettingType::Timezone, $specificUser);
    }

    public function testRemoveDoesNothingWhenNoUserLoggedIn(): void
    {
        $this->security->shouldReceive('getUser')->andReturn(null);
        $this->repository->shouldNotReceive('remove');

        $config = new UserConfig($this->repository, $this->security);
        $config->remove(UserSettingType::Timezone);
    }

    public function testGetAllWithCurrentUser(): void
    {
        $expectedSettings = [
            'timezone' => 'Europe/London',
            'location' => 'London',
        ];

        $this->security->shouldReceive('getUser')->andReturn($this->user);
        $this->repository->shouldReceive('getAllForUser')
            ->with($this->user)
            ->andReturn($expectedSettings);

        $config = new UserConfig($this->repository, $this->security);

        self::assertSame($expectedSettings, $config->getAll());
    }

    public function testGetAllWithSpecificUser(): void
    {
        $specificUser = new User();
        $specificUser->setEmail('other@example.com');

        $expectedSettings = [
            'timezone' => 'America/New_York',
        ];

        $this->repository->shouldReceive('getAllForUser')
            ->with($specificUser)
            ->andReturn($expectedSettings);

        $config = new UserConfig($this->repository, $this->security);

        self::assertSame($expectedSettings, $config->getAll($specificUser));
    }

    public function testGetAllReturnsEmptyArrayWhenNoUserLoggedIn(): void
    {
        $this->security->shouldReceive('getUser')->andReturn(null);

        $config = new UserConfig($this->repository, $this->security);

        self::assertSame([], $config->getAll());
    }

    private function createSetting(UserSettingType $key, ?string $value): UserSetting
    {
        $setting = new UserSetting();
        $setting->setUser($this->user);
        $setting->setKey($key);
        $setting->setValue($value);

        return $setting;
    }
}
