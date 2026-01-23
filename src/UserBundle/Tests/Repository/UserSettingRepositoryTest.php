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

namespace SolidInvoice\UserBundle\Tests\Repository;

use Doctrine\ORM\EntityManagerInterface;
use SolidInvoice\CoreBundle\Test\Traits\FakerTestTrait;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Entity\UserSetting;
use SolidInvoice\UserBundle\Enum\UserSettingType;
use SolidInvoice\UserBundle\Repository\UserSettingRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @covers \SolidInvoice\UserBundle\Repository\UserSettingRepository
 */
final class UserSettingRepositoryTest extends KernelTestCase
{
    use EnsureApplicationInstalled;
    use FakerTestTrait;

    private UserSettingRepository $repository;

    private EntityManagerInterface $em;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $registry = self::getContainer()->get('doctrine');
        $em = $registry->getManager();
        assert($em instanceof EntityManagerInterface);
        $this->em = $em;
        $repository = self::getContainer()->get(UserSettingRepository::class);
        assert($repository instanceof UserSettingRepository);
        $this->repository = $repository;

        // Create a test user
        $this->user = new User();
        $this->user->setEmail($this->getFaker()->email())
            ->setPassword($this->getFaker()->password())
            ->addCompany($this->company);

        $this->em->persist($this->user);
        $this->em->flush();
    }

    protected function tearDown(): void
    {
        // Clean up user settings
        foreach ($this->repository->findAll() as $setting) {
            $this->em->remove($setting);
        }

        $this->em->flush();

        parent::tearDown();
    }

    public function testGetSetting(): void
    {
        $setting = new UserSetting();
        $setting->setUser($this->user);
        $setting->setKey(UserSettingType::Timezone);
        $setting->setValue('Europe/London');

        $this->em->persist($setting);
        $this->em->flush();

        $result = $this->repository->getSetting($this->user, UserSettingType::Timezone);

        self::assertNotNull($result);
        self::assertSame('Europe/London', $result->getValue());
        self::assertSame(UserSettingType::Timezone, $result->getKey());
        self::assertTrue($this->user->getId()->equals($result->getUser()->getId()));
    }

    public function testGetSettingReturnsNullWhenNotFound(): void
    {
        $result = $this->repository->getSetting($this->user, UserSettingType::Timezone);

        self::assertNull($result);
    }

    public function testSaveSettingCreatesNewSetting(): void
    {
        $this->repository->saveSetting($this->user, UserSettingType::Timezone, 'America/New_York');

        $setting = $this->repository->getSetting($this->user, UserSettingType::Timezone);

        self::assertNotNull($setting);
        self::assertSame('America/New_York', $setting->getValue());
        self::assertSame(UserSettingType::Timezone, $setting->getKey());
    }

    public function testSaveSettingUpdatesExistingSetting(): void
    {
        // Create initial setting
        $this->repository->saveSetting($this->user, UserSettingType::Timezone, 'Europe/London');

        // Update the setting
        $this->repository->saveSetting($this->user, UserSettingType::Timezone, 'Asia/Tokyo');

        $setting = $this->repository->getSetting($this->user, UserSettingType::Timezone);

        self::assertNotNull($setting);
        self::assertSame('Asia/Tokyo', $setting->getValue());

        // Ensure only one setting exists
        self::assertCount(1, $this->repository->findAll());
    }

    public function testSaveSettingWithNullValue(): void
    {
        $this->repository->saveSetting($this->user, UserSettingType::Location, null);

        $setting = $this->repository->getSetting($this->user, UserSettingType::Location);

        self::assertNotNull($setting);
        self::assertNull($setting->getValue());
    }

    public function testRemove(): void
    {
        // Create a setting
        $this->repository->saveSetting($this->user, UserSettingType::Timezone, 'Europe/London');

        self::assertNotNull($this->repository->getSetting($this->user, UserSettingType::Timezone));

        // Remove it
        $this->repository->removeSetting($this->user, UserSettingType::Timezone);

        self::assertNull($this->repository->getSetting($this->user, UserSettingType::Timezone));
    }

    public function testRemoveDoesNothingWhenSettingDoesNotExist(): void
    {
        // This should not throw an exception
        $this->repository->removeSetting($this->user, UserSettingType::Timezone);

        self::assertNull($this->repository->getSetting($this->user, UserSettingType::Timezone));
    }

    public function testGetAllForUser(): void
    {
        $this->repository->saveSetting($this->user, UserSettingType::Timezone, 'Europe/London');
        $this->repository->saveSetting($this->user, UserSettingType::Location, 'London');
        $this->repository->saveSetting($this->user, UserSettingType::OnboardComplete, '1');

        $result = $this->repository->getAllForUser($this->user);

        self::assertCount(3, $result);
        self::assertSame('Europe/London', $result['timezone']);
        self::assertSame('London', $result['location']);
        self::assertSame('1', $result['onboard_complete']);
    }

    public function testGetAllForUserReturnsEmptyArrayWhenNoSettings(): void
    {
        $result = $this->repository->getAllForUser($this->user);

        self::assertSame([], $result);
    }

    public function testGetAllForUserOnlyReturnsSettingsForSpecificUser(): void
    {
        // Create another user
        $otherUser = new User();
        $otherUser->setEmail($this->getFaker()->email())
            ->setPassword($this->getFaker()->password())
            ->addCompany($this->company);

        $this->em->persist($otherUser);
        $this->em->flush();

        // Create settings for both users
        $this->repository->saveSetting($this->user, UserSettingType::Timezone, 'Europe/London');
        $this->repository->saveSetting($otherUser, UserSettingType::Timezone, 'America/New_York');
        $this->repository->saveSetting($otherUser, UserSettingType::Location, 'New York');

        // Get settings for first user
        $result = $this->repository->getAllForUser($this->user);

        self::assertCount(1, $result);
        self::assertSame('Europe/London', $result['timezone']);
        self::assertArrayNotHasKey('location', $result);
    }
}
