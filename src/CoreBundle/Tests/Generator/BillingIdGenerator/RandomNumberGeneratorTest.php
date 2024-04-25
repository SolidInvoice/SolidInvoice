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
use Random\RandomException;
use SolidInvoice\CoreBundle\Generator\BillingIdGenerator\RandomNumberGenerator;
use stdClass;

/**
 * @covers \SolidInvoice\CoreBundle\Generator\BillingIdGenerator\RandomNumberGenerator
 */
final class RandomNumberGeneratorTest extends TestCase
{
    /**
     * @throws RandomException
     */
    public function testGenerate(): void
    {
        $generator = new RandomNumberGenerator();

        $value1 = $generator->generate(new stdClass(), []);
        self::assertGreaterThan(RandomNumberGenerator::MIN_VALUE, $value1);
        self::assertLessThan(RandomNumberGenerator::MAX_VALUE, $value1);

        self::assertNotSame($value1, $generator->generate(new stdClass(), []));

        $value2 = $generator->generate(new stdClass(), ['min' => 100, 'max' => 200]);
        self::assertGreaterThan(100, $value2);
        self::assertLessThan(200, $value2);

        self::assertNotSame($value2, $generator->generate(new stdClass(), ['min' => 100, 'max' => 200]));
    }

    public function testGenerateWithLength(): void
    {
        self::assertSame('random_number', RandomNumberGenerator::getName());
    }

    public function testGetConfigurationFormType(): void
    {
        self::assertNull((new RandomNumberGenerator())->getConfigurationFormType());
    }
}
