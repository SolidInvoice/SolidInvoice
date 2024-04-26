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
use SolidInvoice\CoreBundle\Generator\BillingIdGenerator\UlidGenerator;
use stdClass;
use Symfony\Component\Uid\Ulid;

/**
 * @covers \SolidInvoice\CoreBundle\Generator\BillingIdGenerator\UlidGenerator
 */
final class UlidGeneratorTest extends TestCase
{
    public function testGenerate(): void
    {
        $generator = new UlidGenerator();

        $value = $generator->generate(new stdClass(), []);
        self::assertTrue(Ulid::isValid($value));
    }

    public function testGenerateWithLength(): void
    {
        self::assertSame('ulid', UlidGenerator::getName());
    }

    public function testGetConfigurationFormType(): void
    {
        self::assertNull((new UlidGenerator())->getConfigurationFormType());
    }
}
