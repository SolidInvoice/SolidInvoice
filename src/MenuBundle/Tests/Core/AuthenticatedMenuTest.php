<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\MenuBundle\Tests\Core;

use SolidInvoice\MenuBundle\Core\AuthenticatedMenu;
use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;

class AuthenticatedMenuTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\MockInterface
     */
    private $container;

    /**
     * @var \Mockery\MockInterface
     */
    private $security;

    public function setUp()
    {
        $this->container = M::mock('Symfony\Component\DependencyInjection\ContainerInterface');
        $this->security = M::mock('Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface');
    }

    public function testValidate()
    {
        $menu = new AuthenticatedMenu();
        $menu->setContainer($this->container);

        $this->container->shouldReceive('get')
            ->once()
            ->withArgs(['security.authorization_checker'])
            ->andReturn($this->security);

        $this->security->shouldReceive('isGranted')
            ->once()
            ->withArgs(['IS_AUTHENTICATED_REMEMBERED'])
            ->andReturn(true);

        $this->assertTrue($menu->validate());

        $this->security->shouldHaveReceived('isGranted')->once()->withArgs(['IS_AUTHENTICATED_REMEMBERED']);
    }

    public function testValidateFail()
    {
        $menu = new AuthenticatedMenu();
        $menu->setContainer($this->container);

        $this->container->shouldReceive('get')
            ->once()
            ->withArgs(['security.authorization_checker'])
            ->andReturn($this->security);

        $this->security->shouldReceive('isGranted')
            ->once()
            ->withArgs(['IS_AUTHENTICATED_REMEMBERED'])
            ->andReturn(false);

        $this->assertFalse($menu->validate());

        $this->security->shouldHaveReceived('isGranted')->once()->withArgs(['IS_AUTHENTICATED_REMEMBERED']);
    }

    public function testValidateFailException()
    {
        $menu = new AuthenticatedMenu();
        $menu->setContainer($this->container);

        $this->container->shouldReceive('get')
            ->once()
            ->withArgs(['security.authorization_checker'])
            ->andThrow(new AuthenticationCredentialsNotFoundException());

        $this->assertFalse($menu->validate());

        $this->security->shouldNotHaveReceived('isGranted');
    }
}
