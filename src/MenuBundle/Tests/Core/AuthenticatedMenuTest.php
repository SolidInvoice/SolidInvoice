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

namespace SolidInvoice\MenuBundle\Tests\Core;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use SolidInvoice\MenuBundle\Core\AuthenticatedMenu;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;

class AuthenticatedMenuTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var MockInterface&AuthorizationCheckerInterface
     */
    private $security;

    protected function setUp(): void
    {
        $this->security = M::mock(AuthorizationCheckerInterface::class);
    }

    public function testValidate(): void
    {
        $menu = new AuthenticatedMenu($this->security);

        $this->security->shouldReceive('isGranted')
            ->once()
            ->withArgs(['IS_AUTHENTICATED_REMEMBERED'])
            ->andReturn(true);

        self::assertTrue($menu->validate());

        $this->security->shouldHaveReceived('isGranted')->once()->withArgs(['IS_AUTHENTICATED_REMEMBERED']);
    }

    public function testValidateFail(): void
    {
        $menu = new AuthenticatedMenu($this->security);

        $this->security->shouldReceive('isGranted')
            ->once()
            ->withArgs(['IS_AUTHENTICATED_REMEMBERED'])
            ->andReturn(false);

        self::assertFalse($menu->validate());

        $this->security->shouldHaveReceived('isGranted')->once()->withArgs(['IS_AUTHENTICATED_REMEMBERED']);
    }

    public function testValidateFailException(): void
    {
        $menu = new AuthenticatedMenu($this->security);

        $this->security
            ->shouldReceive('isGranted')
            ->once()
            ->withArgs(['IS_AUTHENTICATED_REMEMBERED'])
            ->andThrow(new AuthenticationCredentialsNotFoundException());

        self::assertFalse($menu->validate());

        $this->security->shouldHaveReceived('isGranted')->once()->withArgs(['IS_AUTHENTICATED_REMEMBERED']);
    }
}
