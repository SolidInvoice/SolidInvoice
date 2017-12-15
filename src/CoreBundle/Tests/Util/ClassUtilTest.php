<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\CoreBundle\Tests\Util;

use SolidInvoice\CoreBundle\Util\ClassUtil;
use PHPUnit\Framework\TestCase;

class ClassUtilTest extends TestCase
{
    public function testFindClassInFile()
    {
        $this->assertSame(ClassUtilTest::class, ClassUtil::findClassInFile(__FILE__));
    }

    public function testFindClassInFileWithInvalidFile()
    {
        $this->assertNull(ClassUtil::findClassInFile(dirname(__DIR__).'/Fixtures/file.php'));
    }
}
