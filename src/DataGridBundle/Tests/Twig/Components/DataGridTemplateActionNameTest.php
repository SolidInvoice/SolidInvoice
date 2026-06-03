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

namespace SolidInvoice\DataGridBundle\Tests\Twig\Components;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use SolidInvoice\DataGridBundle\Twig\Components\DataGrid;
use Symfony\UX\LiveComponent\Attribute\LiveAction;

/**
 * Regression test for https://github.com/SolidInvoice/SolidInvoice/issues/2428:
 * The DataGrid template was referencing "executeSingleAction" which does not exist —
 * the correct LiveAction method name is "executeSingle".
 */
final class DataGridTemplateActionNameTest extends TestCase
{
    private const string TEMPLATE_PATH = __DIR__ . '/../../../Resources/views/Components/DataGrid.html.twig';

    public function testTemplatDoesNotReferenceNonexistentExecuteSingleActionName(): void
    {
        $content = file_get_contents(self::TEMPLATE_PATH);
        self::assertIsString($content);

        self::assertStringNotContainsString(
            'executeSingleAction',
            $content,
            'Template must use "executeSingle", not "executeSingleAction" — the latter does not exist on the DataGrid component.',
        );
    }

    public function testTemplateReferencesExecuteSingleLiveAction(): void
    {
        $content = file_get_contents(self::TEMPLATE_PATH);
        self::assertIsString($content);

        self::assertStringContainsString(
            '"executeSingle"',
            $content,
            'Template must reference the executeSingle live action.',
        );
    }

    public function testExecuteSingleMethodExistsWithLiveActionAttribute(): void
    {
        $reflection = new ReflectionClass(DataGrid::class);

        self::assertTrue(
            $reflection->hasMethod('executeSingle'),
            'DataGrid must have an executeSingle method.',
        );

        $attrs = $reflection->getMethod('executeSingle')->getAttributes(LiveAction::class);
        self::assertNotEmpty(
            $attrs,
            'executeSingle must carry the #[LiveAction] attribute so the Live Component router can find it.',
        );
    }

    public function testExecuteSingleActionMethodDoesNotExist(): void
    {
        $reflection = new ReflectionClass(DataGrid::class);

        self::assertFalse(
            $reflection->hasMethod('executeSingleAction'),
            'There must be no "executeSingleAction" method — the template should use "executeSingle".',
        );
    }

    /**
     * Regression tests for https://github.com/SolidInvoice/SolidInvoice/issues/2430:
     * The DataGrid template was referencing "executeBatchAction" which does not exist —
     * the correct LiveAction method name is "executeBatch".
     */
    public function testTemplateDoesNotReferenceNonexistentExecuteBatchActionName(): void
    {
        $content = file_get_contents(self::TEMPLATE_PATH);
        self::assertIsString($content);

        self::assertStringNotContainsString(
            'executeBatchAction',
            $content,
            'Template must use "executeBatch", not "executeBatchAction" — the latter does not exist on the DataGrid component.',
        );
    }

    public function testTemplateReferencesExecuteBatchLiveAction(): void
    {
        $content = file_get_contents(self::TEMPLATE_PATH);
        self::assertIsString($content);

        self::assertStringContainsString(
            'executeBatch"',
            $content,
            'Template must reference the executeBatch live action.',
        );
    }

    public function testExecuteBatchMethodExistsWithLiveActionAttribute(): void
    {
        $reflection = new ReflectionClass(DataGrid::class);

        self::assertTrue(
            $reflection->hasMethod('executeBatch'),
            'DataGrid must have an executeBatch method.',
        );

        $attrs = $reflection->getMethod('executeBatch')->getAttributes(LiveAction::class);
        self::assertNotEmpty(
            $attrs,
            'executeBatch must carry the #[LiveAction] attribute so the Live Component router can find it.',
        );
    }

    public function testExecuteBatchActionMethodDoesNotExist(): void
    {
        $reflection = new ReflectionClass(DataGrid::class);

        self::assertFalse(
            $reflection->hasMethod('executeBatchAction'),
            'There must be no "executeBatchAction" method — the template should use "executeBatch".',
        );
    }
}
