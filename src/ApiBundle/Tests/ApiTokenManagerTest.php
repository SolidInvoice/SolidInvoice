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

        static::assertIsString($token);
        static::assertSame(64, strlen($token));
        static::assertMatchesRegularExpression('/[a-zA-Z0-9]{64}/', $token);
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

        static::assertInstanceOf(ApiToken::class, $token);
        static::assertSame($user, $token->getUser());
        static::assertSame('test token', $token->getName());
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

        static::assertInstanceOf(ApiToken::class, $token);
        static::assertSame($token1, $token);
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

        static::assertInstanceOf(ApiToken::class, $token);
        static::assertNotSame($token1, $token);
        static::assertNotSame($token2, $token);
        static::assertSame($user, $token->getUser());
        static::assertSame('token3', $token->getName());
    }
}
