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

namespace SolidInvoice\CoreBundle\Tests\Generator\BillingIdGenerator;

use Carbon\Carbon;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Generator\BillingIdGenerator\TimestampGenerator;
use stdClass;

#[CoversClass(TimestampGenerator::class)]
final class TimestampGeneratorTest extends TestCase
{
    public function testGenerate(): void
    {
        $generator = new TimestampGenerator();

        self::assertSame(Carbon::now()->format('YmdHis'), $generator->generate(new stdClass(), []));
        self::assertSame(Carbon::now()->format('Y-m-d H:i:s'), $generator->generate(new stdClass(), ['format' => 'Y-m-d H:i:s']));
    }

    public function testGenerateWithLength(): void
    {
        self::assertSame('timestamp', TimestampGenerator::getName());
    }

    public function testGetConfigurationFormType(): void
    {
        self::assertNull(new TimestampGenerator()->getConfigurationFormType());
    }
}
