<?php
/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\ApiBundle\Tests;

use CSBill\ApiBundle\ApiTokenManager;
use CSBill\UserBundle\Entity\ApiToken;
use CSBill\UserBundle\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Mockery as M;

class ApiTokenManagerTest extends \PHPUnit_Framework_TestCase
{
    public function testGenerateToken()
    {
        $tm = new ApiTokenManager(M::mock(ManagerRegistry::class));

        $token = $tm->generateToken();

        $this->assertTrue(is_string($token));
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

        $user->setApiTokens([$token1, $token2,]);

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
