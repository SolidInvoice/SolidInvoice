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

namespace SolidInvoice\EInvoiceBundle\Tests\Enum;

use PHPUnit\Framework\TestCase;
use SolidInvoice\EInvoiceBundle\Enum\ViolationSeverity;
use UnhandledMatchError;

final class ViolationSeverityTest extends TestCase
{
    public function testFromSchematronFlagMapsFatalToError(): void
    {
        self::assertSame(ViolationSeverity::Error, ViolationSeverity::fromSchematronFlag('fatal'));
    }

    public function testFromSchematronFlagMapsWarningToWarning(): void
    {
        self::assertSame(ViolationSeverity::Warning, ViolationSeverity::fromSchematronFlag('warning'));
    }

    /**
     * `fromSchematronFlag()` has a `default` arm only because PHPStan cannot prove the
     * match is exhaustive over the open string domain. That arm may only throw. It must
     * never return a severity for an unrecognised flag.
     */
    public function testFromSchematronFlagThrowsOnAnUnrecognisedFlag(): void
    {
        $this->expectException(UnhandledMatchError::class);

        ViolationSeverity::fromSchematronFlag('information');
    }

    public function testHasExactlyTwoCases(): void
    {
        self::assertCount(2, ViolationSeverity::cases());
    }
}
