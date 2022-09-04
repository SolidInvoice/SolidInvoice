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

        self::assertIsString($token);
        self::assertSame(64, strlen($token));
        self::assertMatchesRegularExpression('/[a-zA-Z0-9]{64}/', $token);
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

        self::assertInstanceOf(ApiToken::class, $token);
        self::assertSame($user, $token->getUser());
        self::assertSame('test token', $token->getName());
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

        self::assertInstanceOf(ApiToken::class, $token);
        self::assertSame($token1, $token);
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

        self::assertInstanceOf(ApiToken::class, $token);
        self::assertNotSame($token1, $token);
        self::assertNotSame($token2, $token);
        self::assertSame($user, $token->getUser());
        self::assertSame('token3', $token->getName());
    }
}
