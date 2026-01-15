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

namespace SolidInvoice\CoreBundle\Tests\Pdf;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SolidInvoice\CoreBundle\Pdf\Generator;

class GeneratorTest extends TestCase
{
    public function testGenerate(): void
    {
        /** @var LoggerInterface&MockObject $logger */
        $logger = $this->createMock(LoggerInterface::class);

        $logger->expects($this->exactly(5))
            ->method('debug');

        $generator = new Generator(sys_get_temp_dir(), $logger);
        $output = $generator->generate('<body>Hello World</body>');
        self::assertStringStartsWith('%PDF-', $output);
    }

    public function testCanPrintPdf(): void
    {
        $generator = new Generator(sys_get_temp_dir(), new NullLogger());

        if (\extension_loaded('mbstring') && \extension_loaded('gd')) {
            self::assertTrue($generator->canPrintPdf());
        } else {
            self::assertFalse($generator->canPrintPdf());
        }
    }
}
