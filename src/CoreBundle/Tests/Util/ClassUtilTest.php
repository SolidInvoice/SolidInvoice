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

namespace SolidInvoice\CoreBundle\Tests\Util;

use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Util\ClassUtil;

class ClassUtilTest extends TestCase
{
    public function testFindClassInFile(): void
    {
        self::assertSame(self::class, ClassUtil::findClassInFile(__FILE__));
    }

    public function testFindClassInFileWithInvalidFile(): void
    {
        self::assertNull(ClassUtil::findClassInFile(dirname(__DIR__) . '/Fixtures/file.php'));
    }
}
