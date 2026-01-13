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

namespace SolidInvoice\DashboardBundle\Tests\Checklist\DTO;

use PHPUnit\Framework\TestCase;
use SolidInvoice\DashboardBundle\Checklist\DTO\ChecklistItemDTO;
use SolidInvoice\DashboardBundle\Checklist\DTO\ChecklistProgressDTO;

final class ChecklistProgressDTOTest extends TestCase
{
    public function testConstructorSetsAllProperties(): void
    {
        $item1 = new ChecklistItemDTO('Item 1', 'Description 1', 'icon1', 'route1', false);
        $item2 = new ChecklistItemDTO('Item 2', 'Description 2', 'icon2', 'route2', true);
        $items = [$item1, $item2];

        $dto = new ChecklistProgressDTO($items, 1, 2, 50, false);

        self::assertSame($items, $dto->items);
        self::assertSame(1, $dto->completed);
        self::assertSame(2, $dto->total);
        self::assertSame(50, $dto->percentage);
        self::assertFalse($dto->allComplete);
    }

    public function testPropertiesAreReadonly(): void
    {
        $dto = new ChecklistProgressDTO([], 0, 0, 0, true);

        self::assertIsArray($dto->items);
        self::assertIsInt($dto->completed);
        self::assertIsInt($dto->total);
        self::assertIsInt($dto->percentage);
        self::assertIsBool($dto->allComplete);
    }

    public function testCanCreateWithEmptyItems(): void
    {
        $dto = new ChecklistProgressDTO([], 0, 0, 0, true);

        self::assertEmpty($dto->items);
        self::assertSame(0, $dto->completed);
        self::assertSame(0, $dto->total);
        self::assertSame(0, $dto->percentage);
        self::assertTrue($dto->allComplete);
    }

    public function testCanCreateWithMultipleItems(): void
    {
        $items = [
            new ChecklistItemDTO('Item 1', 'Desc 1', 'icon1', 'route1', true),
            new ChecklistItemDTO('Item 2', 'Desc 2', 'icon2', 'route2', true),
            new ChecklistItemDTO('Item 3', 'Desc 3', 'icon3', 'route3', false),
        ];

        $dto = new ChecklistProgressDTO($items, 2, 3, 66, false);

        self::assertCount(3, $dto->items);
        self::assertSame(2, $dto->completed);
        self::assertSame(3, $dto->total);
        self::assertSame(66, $dto->percentage);
        self::assertFalse($dto->allComplete);
    }

    public function testAllCompleteCanBeTrue(): void
    {
        $items = [
            new ChecklistItemDTO('Item 1', 'Desc 1', 'icon1', 'route1', true),
            new ChecklistItemDTO('Item 2', 'Desc 2', 'icon2', 'route2', true),
        ];

        $dto = new ChecklistProgressDTO($items, 2, 2, 100, true);

        self::assertTrue($dto->allComplete);
    }
}
