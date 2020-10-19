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

namespace SolidInvoice\ApiBundle\Tests;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use PHPUnit\Framework\TestCase;
use SolidInvoice\ApiBundle\ApiTokenManager;
use SolidInvoice\UserBundle\Entity\ApiToken;
use SolidInvoice\UserBundle\Entity\User;

class ApiTokenManagerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testGenerateToken()
    {
        $tm = new ApiTokenManager(M::mock(ManagerRegistry::class));

        $token = $tm->generateToken();

        $this->assertIsString($token);
        $this->assertSame(64, strlen($token));
        $this->assertRegExp('/[a-zA-Z0-9]{64}/', $token);
    }

    public function testCreate()
    {
        $registry = M::mock(ManagerRegistry::class);

        $user = new User();

        $manager = M::mock(ObjectManager::class);

        $registry->shouldReceive('getManager')
            ->withNoArgs()
            ->andReturn($manager);

        $manager->shouldReceive('persist')
            ->withAnyArgs()
            ->andReturn();

        $manager->shouldReceive('flush')
            ->withNoArgs();

        $tm = new ApiTokenManager($registry);

        $token = $tm->create($user, 'test token');

        $this->assertInstanceOf(ApiToken::class, $token);
        $this->assertSame($user, $token->getUser());
        $this->assertSame('test token', $token->getName());
    }

    public function testGet()
    {
        $registry = M::mock(ManagerRegistry::class);

        $user = new User();

        $token1 = new ApiToken();
        $token1->setName('token1');

        $token2 = new ApiToken();
        $token2->setName('token2');

        $user->setApiTokens(new ArrayCollection([$token1, $token2]));

        $tm = new ApiTokenManager($registry);

        $token = $tm->getOrCreate($user, 'token1');

        $this->assertInstanceOf(ApiToken::class, $token);
        $this->assertSame($token1, $token);
    }

    public function testGetOrCreate()
    {
        $registry = M::mock(ManagerRegistry::class);

        $user = new User();

        $token1 = new ApiToken();
        $token1->setName('token1');

        $token2 = new ApiToken();
        $token2->setName('token2');

        $user->setApiTokens(new ArrayCollection([$token1, $token2]));

        $manager = M::mock(ObjectManager::class);

        $registry->shouldReceive('getManager')
            ->withNoArgs()
            ->andReturn($manager);

        $manager->shouldReceive('persist')
            ->withAnyArgs()
            ->andReturn();

        $manager->shouldReceive('flush')
            ->withNoArgs();

        $tm = new ApiTokenManager($registry);

        $token = $tm->getOrCreate($user, 'token3');

        $this->assertInstanceOf(ApiToken::class, $token);
        $this->assertNotSame($token1, $token);
        $this->assertNotSame($token2, $token);
        $this->assertSame($user, $token->getUser());
        $this->assertSame('token3', $token->getName());
    }
}
