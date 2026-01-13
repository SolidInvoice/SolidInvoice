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

final class ChecklistItemDTOTest extends TestCase
{
    public function testConstructorSetsAllProperties(): void
    {
        $dto = new ChecklistItemDTO(
            'Test Item',
            'Test Description',
            'test-icon',
            'test_route',
            true
        );

        self::assertSame('Test Item', $dto->name);
        self::assertSame('Test Description', $dto->description);
        self::assertSame('test-icon', $dto->icon);
        self::assertSame('test_route', $dto->route);
        self::assertTrue($dto->completed);
    }

    public function testPropertiesAreReadonly(): void
    {
        $dto = new ChecklistItemDTO(
            'Name',
            'Description',
            'icon',
            'route',
            false
        );

        self::assertIsString($dto->name);
        self::assertIsString($dto->description);
        self::assertIsString($dto->icon);
        self::assertIsString($dto->route);
        self::assertIsBool($dto->completed);
    }

    public function testCompletedCanBeFalse(): void
    {
        $dto = new ChecklistItemDTO(
            'Incomplete Item',
            'Not done yet',
            'icon',
            'route',
            false
        );

        self::assertFalse($dto->completed);
    }

    public function testCompletedCanBeTrue(): void
    {
        $dto = new ChecklistItemDTO(
            'Complete Item',
            'Already done',
            'icon',
            'route',
            true
        );

        self::assertTrue($dto->completed);
    }

    public function testCanCreateWithEmptyStrings(): void
    {
        $dto = new ChecklistItemDTO('', '', '', '', false);

        self::assertSame('', $dto->name);
        self::assertSame('', $dto->description);
        self::assertSame('', $dto->icon);
        self::assertSame('', $dto->route);
        self::assertFalse($dto->completed);
    }

    public function testCanCreateWithDifferentRouteFormats(): void
    {
        $dto1 = new ChecklistItemDTO('Item', 'Desc', 'icon', '_clients_index', false);
        $dto2 = new ChecklistItemDTO('Item', 'Desc', 'icon', 'app_settings', false);
        $dto3 = new ChecklistItemDTO('Item', 'Desc', 'icon', '/custom/path', false);

        self::assertSame('_clients_index', $dto1->route);
        self::assertSame('app_settings', $dto2->route);
        self::assertSame('/custom/path', $dto3->route);
    }

    public function testCanCreateWithDifferentIconFormats(): void
    {
        $dto1 = new ChecklistItemDTO('Item', 'Desc', 'tabler:users', 'route', false);
        $dto2 = new ChecklistItemDTO('Item', 'Desc', 'fa-icon', 'route', false);
        $dto3 = new ChecklistItemDTO('Item', 'Desc', 'custom-icon-name', 'route', false);

        self::assertSame('tabler:users', $dto1->icon);
        self::assertSame('fa-icon', $dto2->icon);
        self::assertSame('custom-icon-name', $dto3->icon);
    }
}
