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

namespace SolidInvoice\CoreBundle\Tests\Enum;

use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Enum\CustomFieldType;

final class CustomFieldTypeTest extends TestCase
{
    public function testCases(): void
    {
        self::assertCount(9, CustomFieldType::cases());
        self::assertSame('text', CustomFieldType::TEXT->value);
        self::assertSame('multi_select', CustomFieldType::MULTI_SELECT->value);
    }

    public function testRequiresOptions(): void
    {
        self::assertTrue(CustomFieldType::SELECT->requiresOptions());
        self::assertTrue(CustomFieldType::MULTI_SELECT->requiresOptions());
        self::assertFalse(CustomFieldType::TEXT->requiresOptions());
        self::assertFalse(CustomFieldType::NUMBER->requiresOptions());
    }

    public function testLabel(): void
    {
        self::assertSame('Text', CustomFieldType::TEXT->label());
        self::assertSame('Multi-select', CustomFieldType::MULTI_SELECT->label());
    }
}
