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
     * This test exists to stop a `default` arm being added to `fromSchematronFlag()`.
     * An unrecognised flag means the rule set grew a vocabulary we have not read, and
     * that must throw rather than silently fall back to a guessed severity.
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
