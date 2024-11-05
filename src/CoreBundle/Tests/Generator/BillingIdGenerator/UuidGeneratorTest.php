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
use SolidInvoice\CoreBundle\Generator\BillingIdGenerator\UuidGenerator;
use stdClass;
use Symfony\Component\Uid\Uuid;

/**
 * @covers \SolidInvoice\CoreBundle\Generator\BillingIdGenerator\UuidGenerator
 */
final class UuidGeneratorTest extends TestCase
{
    public function testGenerate(): void
    {
        $generator = new UuidGenerator();

        $value = $generator->generate(new stdClass(), []);
        self::assertTrue(Uuid::isValid($value));
    }

    public function testGenerateWithLength(): void
    {
        self::assertSame('uuid', UuidGenerator::getName());
    }

    public function testGetConfigurationFormType(): void
    {
        self::assertNull((new UuidGenerator())->getConfigurationFormType());
    }
}
