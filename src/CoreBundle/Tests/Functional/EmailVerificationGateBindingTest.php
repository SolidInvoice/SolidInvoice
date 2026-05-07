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

namespace SolidInvoice\CoreBundle\Tests\Functional;

use SolidInvoice\CoreBundle\Contracts\EmailVerificationGateInterface;
use SolidInvoice\CoreBundle\Email\NullEmailVerificationGate;
use SolidInvoice\CoreBundle\Entity\Company;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class EmailVerificationGateBindingTest extends KernelTestCase
{
    private function skipIfSaasLoaded(): void
    {
        $bundles = self::$kernel->getBundles();

        if (isset($bundles['SolidWorxPlatformSaasBundle']) || isset($bundles['SolidInvoiceSaasBundle'])) {
            self::markTestSkipped('Test kernel has SaaS bundles loaded; this regression test is for non-SaaS mode only.');
        }
    }

    public function testNullGateIsBoundWhenSaasBundleNotLoaded(): void
    {
        self::bootKernel();
        $this->skipIfSaasLoaded();

        $gate = self::getContainer()->get(EmailVerificationGateInterface::class);
        self::assertInstanceOf(NullEmailVerificationGate::class, $gate);
    }

    public function testNullGateIsGatedReturnsFalse(): void
    {
        self::bootKernel();
        $this->skipIfSaasLoaded();

        $gate = self::getContainer()->get(EmailVerificationGateInterface::class);
        self::assertFalse($gate->isGated());
    }

    public function testNullGateIsCompanyGatedReturnsFalse(): void
    {
        self::bootKernel();
        $this->skipIfSaasLoaded();

        $gate = self::getContainer()->get(EmailVerificationGateInterface::class);
        self::assertFalse($gate->isCompanyGated(new Company()));
    }

    public function testNullGateReasonReturnsEmptyString(): void
    {
        self::bootKernel();
        $this->skipIfSaasLoaded();

        $gate = self::getContainer()->get(EmailVerificationGateInterface::class);
        self::assertSame('', $gate->reason('do anything'));
    }
}
