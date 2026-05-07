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

namespace SolidInvoice\CoreBundle\Tests\Twig\Extension;

use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Contracts\EmailVerificationGateInterface;
use SolidInvoice\CoreBundle\Twig\Extension\EmailVerificationExtension;

final class EmailVerificationExtensionTest extends TestCase
{
    public function testIsGatedDelegatesToGate(): void
    {
        $gate = $this->createMock(EmailVerificationGateInterface::class);
        $gate->expects(self::once())->method('isGated')->willReturn(true);

        self::assertTrue((new EmailVerificationExtension($gate))->isEmailVerificationGated());
    }

    public function testMessageDelegatesToGate(): void
    {
        $gate = $this->createMock(EmailVerificationGateInterface::class);
        $gate->expects(self::once())
            ->method('reason')
            ->with('sending this invoice')
            ->willReturn('Please verify your email address before sending this invoice.');

        self::assertSame(
            'Please verify your email address before sending this invoice.',
            (new EmailVerificationExtension($gate))->emailVerificationMessage('sending this invoice'),
        );
    }
}
