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

namespace SolidInvoice\CoreBundle\Tests\Email;

use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Email\NullEmailVerificationGate;
use SolidInvoice\CoreBundle\Entity\Company;

final class NullEmailVerificationGateTest extends TestCase
{
    public function testIsGatedAlwaysFalse(): void
    {
        self::assertFalse((new NullEmailVerificationGate())->isGated());
    }

    public function testIsCompanyGatedAlwaysFalse(): void
    {
        self::assertFalse((new NullEmailVerificationGate())->isCompanyGated(new Company()));
    }

    public function testReasonReturnsEmptyString(): void
    {
        self::assertSame('', (new NullEmailVerificationGate())->reason('send invoice'));
    }
}
