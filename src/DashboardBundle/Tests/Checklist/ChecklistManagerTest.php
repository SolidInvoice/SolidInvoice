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

namespace SolidInvoice\DashboardBundle\Tests\Checklist;

use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use SolidInvoice\DashboardBundle\Checklist\ChecklistItemInterface;
use SolidInvoice\DashboardBundle\Checklist\ChecklistManager;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\UserBundle\Repository\UserSettingRepository;
use SolidInvoice\UserBundle\Test\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

final class ChecklistManagerTest extends KernelTestCase
{
    use EnsureApplicationInstalled;
    use Factories;

    public function testGetItemsReturnsSortedItemsByPriority(): void
    {
        $item1 = $this->createMockItem('Item 1', 'Description 1', 'icon1', 'route1', 30, false);
        $item2 = $this->createMockItem('Item 2', 'Description 2', 'icon2', 'route2', 10, false);
        $item3 = $this->createMockItem('Item 3', 'Description 3', 'icon3', 'route3', 20, false);

        $userSettingRepository = self::getContainer()->get(UserSettingRepository::class);
        $manager = new ChecklistManager([$item1, $item2, $item3], $userSettingRepository);

        $items = $manager->getItems();

        // Items are sorted descending by priority (highest first)
        self::assertCount(3, $items);
        self::assertSame('Item 1', $items[0]->getName()); // Priority 30
        self::assertSame('Item 3', $items[1]->getName()); // Priority 20
        self::assertSame('Item 2', $items[2]->getName()); // Priority 10
    }

    public function testGetProgressWithNoItems(): void
    {
        $userSettingRepository = self::getContainer()->get(UserSettingRepository::class);
        $manager = new ChecklistManager([], $userSettingRepository);

        $progress = $manager->getProgress();

        self::assertSame(0, $progress->completed);
        self::assertSame(0, $progress->total);
        self::assertSame(0, $progress->percentage);
        // allComplete is false when there are no items (nothing to complete)
        self::assertFalse($progress->allComplete);
        self::assertEmpty($progress->items);
    }

    public function testGetProgressWithAllItemsComplete(): void
    {
        $item1 = $this->createMockItem('Item 1', 'Description 1', 'icon1', 'route1', 10, true);
        $item2 = $this->createMockItem('Item 2', 'Description 2', 'icon2', 'route2', 20, true);

        $userSettingRepository = self::getContainer()->get(UserSettingRepository::class);
        $manager = new ChecklistManager([$item1, $item2], $userSettingRepository);

        $progress = $manager->getProgress();

        self::assertSame(2, $progress->completed);
        self::assertSame(2, $progress->total);
        self::assertSame(100, $progress->percentage);
        self::assertTrue($progress->allComplete);
        self::assertCount(2, $progress->items);
    }

    public function testGetProgressWithPartialCompletion(): void
    {
        $item1 = $this->createMockItem('Item 1', 'Description 1', 'icon1', 'route1', 10, true);
        $item2 = $this->createMockItem('Item 2', 'Description 2', 'icon2', 'route2', 20, false);
        $item3 = $this->createMockItem('Item 3', 'Description 3', 'icon3', 'route3', 30, true);

        $userSettingRepository = self::getContainer()->get(UserSettingRepository::class);
        $manager = new ChecklistManager([$item1, $item2, $item3], $userSettingRepository);

        $progress = $manager->getProgress();

        self::assertSame(2, $progress->completed);
        self::assertSame(3, $progress->total);
        // 2/3 = 66.666...% which rounds to 67%
        self::assertSame(67, $progress->percentage);
        self::assertFalse($progress->allComplete);
        self::assertCount(3, $progress->items);
    }

    public function testGetProgressDTOContainsCorrectItemData(): void
    {
        $item = $this->createMockItem('Test Item', 'Test Description', 'test-icon', 'test_route', 10, false);

        $userSettingRepository = self::getContainer()->get(UserSettingRepository::class);
        $manager = new ChecklistManager([$item], $userSettingRepository);

        $progress = $manager->getProgress();

        self::assertCount(1, $progress->items);
        self::assertSame('Test Item', $progress->items[0]->name);
        self::assertSame('Test Description', $progress->items[0]->description);
        self::assertSame('test-icon', $progress->items[0]->icon);
        self::assertSame('test_route', $progress->items[0]->route);
        self::assertFalse($progress->items[0]->completed);
    }

    public function testIsDismissedReturnsFalseWhenUserHasNotDismissedChecklist(): void
    {
        $company = CompanyFactory::createOne();
        $user = UserFactory::createOne(['companies' => [$company]])->_real();

        $userSettingRepository = self::getContainer()->get(UserSettingRepository::class);
        $manager = new ChecklistManager([], $userSettingRepository);

        self::assertFalse($manager->isDismissed($user));
    }

    public function testDismissSetsUserSetting(): void
    {
        $company = CompanyFactory::createOne();
        $user = UserFactory::createOne(['companies' => [$company]])->_real();

        $userSettingRepository = self::getContainer()->get(UserSettingRepository::class);
        $manager = new ChecklistManager([], $userSettingRepository);

        $manager->dismiss($user);

        self::assertTrue($manager->isDismissed($user));
    }

    public function testShouldShowReturnsFalseWhenDismissed(): void
    {
        $company = CompanyFactory::createOne();
        $user = UserFactory::createOne(['companies' => [$company]])->_real();

        $userSettingRepository = self::getContainer()->get(UserSettingRepository::class);
        $manager = new ChecklistManager([], $userSettingRepository);

        $manager->dismiss($user);

        self::assertFalse($manager->shouldShow($user));
    }

    public function testShouldShowReturnsTrueWhenNotDismissed(): void
    {
        $company = CompanyFactory::createOne();
        $user = UserFactory::createOne(['companies' => [$company]])->_real();

        $userSettingRepository = self::getContainer()->get(UserSettingRepository::class);
        $manager = new ChecklistManager([], $userSettingRepository);

        self::assertTrue($manager->shouldShow($user));
    }

    public function testPercentageCalculationRoundsCorrectly(): void
    {
        $item1 = $this->createMockItem('Item 1', 'Desc 1', 'icon1', 'route1', 10, true);
        $item2 = $this->createMockItem('Item 2', 'Desc 2', 'icon2', 'route2', 20, false);
        $item3 = $this->createMockItem('Item 3', 'Desc 3', 'icon3', 'route3', 30, false);

        $userSettingRepository = self::getContainer()->get(UserSettingRepository::class);
        $manager = new ChecklistManager([$item1, $item2, $item3], $userSettingRepository);

        $progress = $manager->getProgress();

        // 1 out of 3 = 33.333...% should round to 33
        self::assertSame(33, $progress->percentage);
    }

    private function createMockItem(
        string $name,
        string $description,
        string $icon,
        string $route,
        int $priority,
        bool $isComplete
    ): ChecklistItemInterface {
        $item = $this->createMock(ChecklistItemInterface::class);
        $item->method('getName')->willReturn($name);
        $item->method('getDescription')->willReturn($description);
        $item->method('getIcon')->willReturn($icon);
        $item->method('getRoute')->willReturn($route);
        $item->method('getPriority')->willReturn($priority);
        $item->method('isComplete')->willReturn($isComplete);
        $item->method('active')->willReturn(true);

        return $item;
    }
}
