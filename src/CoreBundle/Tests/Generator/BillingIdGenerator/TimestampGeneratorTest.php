<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\CoreBundle\Tests\Generator\BillingIdGenerator;

use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Generator\BillingIdGenerator\TimestampGenerator;
use stdClass;

/**
 * @covers \SolidInvoice\CoreBundle\Generator\BillingIdGenerator\TimestampGenerator
 */
final class TimestampGeneratorTest extends TestCase
{
    public function testGenerate(): void
    {
        $generator = new TimestampGenerator();

        self::assertSame(date('YmdHis'), $generator->generate(new stdClass(), []));
        self::assertSame(date('Y-m-d H:i:s'), $generator->generate(new stdClass(), ['format' => 'Y-m-d H:i:s']));
    }

    public function testGenerateWithLength(): void
    {
        self::assertSame('timestamp', TimestampGenerator::getName());
    }

    public function testGetConfigurationFormType(): void
    {
        self::assertNull((new TimestampGenerator())->getConfigurationFormType());
    }
}
