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

namespace SolidInvoice\CoreBundle\Tests\Pdf;

use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SolidInvoice\CoreBundle\Pdf\Generator;
use PHPUnit\Framework\TestCase;

class GeneratorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testGenerate()
    {
        $logger = M::mock(LoggerInterface::class);

        $logger->expects('debug')
            ->times(5)
            ->withAnyArgs();

        $generator = new Generator(sys_get_temp_dir(), $logger);
        $output = $generator->generate('<body>Hello World</body>');
        $this->assertStringStartsWith('%PDF-', $output);
    }

    public function testCanPrintPdf()
    {
        $generator = new Generator(sys_get_temp_dir(), new NullLogger());

        if (\extension_loaded('mbstring') && \extension_loaded('gd')) {
            $this->assertTrue($generator->canPrintPdf());
        } else {
            $this->assertFalse($generator->canPrintPdf());
        }
    }
}
