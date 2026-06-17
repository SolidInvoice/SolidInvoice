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

namespace SolidInvoice\UserBundle\Tests\Security;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Security\VerifiedUserChecker;
use SolidWorx\Toggler\ToggleInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\Exception\DisabledException;

#[CoversClass(VerifiedUserChecker::class)]
final class VerifiedUserCheckerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testPostAuthThrowsForUnverifiedUserOnSaas(): void
    {
        $checker = new VerifiedUserChecker($this->toggle(saasEnabled: true));

        $this->expectException(CustomUserMessageAccountStatusException::class);

        $checker->checkPostAuth(new User()->setEnabled(true)->setVerified(false));
    }

    public function testPostAuthAllowsVerifiedUserOnSaas(): void
    {
        $checker = new VerifiedUserChecker($this->toggle(saasEnabled: true));

        $checker->checkPostAuth(new User()->setEnabled(true)->setVerified(true));

        $this->expectNotToPerformAssertions();
    }

    public function testPostAuthAllowsUnverifiedUserWhenNotSaas(): void
    {
        // On self-hosted the unverified block does not apply.
        $checker = new VerifiedUserChecker($this->toggle(saasEnabled: false));

        $checker->checkPostAuth(new User()->setEnabled(true)->setVerified(false));

        $this->expectNotToPerformAssertions();
    }

    public function testPreAuthStillBlocksDisabledUser(): void
    {
        // Inherited disabled-account check applies regardless of platform.
        $checker = new VerifiedUserChecker($this->toggle(saasEnabled: false));

        $this->expectException(DisabledException::class);

        $checker->checkPreAuth(new User()->setEnabled(false));
    }

    private function toggle(bool $saasEnabled): ToggleInterface
    {
        $toggle = M::mock(ToggleInterface::class);
        $toggle->shouldReceive('isActive')->with('saas_enabled')->andReturn($saasEnabled);

        return $toggle;
    }
}
