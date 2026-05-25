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
use Doctrine\Common\Collections\Collection;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use PHPUnit\Framework\TestCase;
use SolidInvoice\ApiBundle\ApiTokenManager;
use SolidInvoice\ApiBundle\GeneratedApiToken;
use SolidInvoice\ApiBundle\Security\ApiTokenHasher;
use SolidInvoice\UserBundle\Entity\ApiToken;
use SolidInvoice\UserBundle\Entity\User;

final class ApiTokenManagerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private const string SECRET = 'unit-test-secret';

    private function hasher(): ApiTokenHasher
    {
        return new ApiTokenHasher(self::SECRET);
    }

    public function testGenerateToken(): void
    {
        $tm = new ApiTokenManager(M::mock(ManagerRegistry::class), $this->hasher());

        $token = $tm->generateToken();

        self::assertIsString($token);
        self::assertSame(64, strlen($token));
        self::assertMatchesRegularExpression('/[a-zA-Z0-9]{64}/', $token);
    }

    public function testCreateStoresHashAndReturnsPlaintext(): void
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

        $tm = new ApiTokenManager($registry, $this->hasher());

        $generated = $tm->create($user, 'test token');

        self::assertInstanceOf(GeneratedApiToken::class, $generated);
        self::assertInstanceOf(ApiToken::class, $generated->token);
        self::assertSame($user, $generated->token->getUser());
        self::assertSame('test token', $generated->token->getName());
        self::assertSame(64, strlen($generated->plaintext));
        self::assertNotSame($generated->plaintext, $generated->token->getToken());
        self::assertSame(
            hash_hmac('sha256', $generated->plaintext, self::SECRET),
            $generated->token->getToken(),
        );
    }

    public function testGetReturnsExistingTokenWithoutPlaintext(): void
    {
        $registry = M::mock(ManagerRegistry::class);

        $user = new User();

        $token1 = new ApiToken();
        $token1->setName('token1');

        $token2 = new ApiToken();
        $token2->setName('token2');

        /** @var Collection<int, ApiToken> $apiTokens */
        $apiTokens = new ArrayCollection([$token1, $token2]);
        $user->setApiTokens($apiTokens);

        $tm = new ApiTokenManager($registry, $this->hasher());

        $generated = $tm->getOrCreate($user, 'token1');

        self::assertSame($token1, $generated->token);
        self::assertSame('', $generated->plaintext);
    }

    public function testGetOrCreateCreatesWhenMissing(): void
    {
        $registry = M::mock(ManagerRegistry::class);

        $user = new User();

        $token1 = new ApiToken();
        $token1->setName('token1');

        $token2 = new ApiToken();
        $token2->setName('token2');

        /** @var Collection<int, ApiToken> $apiTokens */
        $apiTokens = new ArrayCollection([$token1, $token2]);
        $user->setApiTokens($apiTokens);

        $manager = M::mock(ObjectManager::class);

        $registry->shouldReceive('getManager')
            ->withNoArgs()
            ->andReturn($manager);

        $manager->shouldReceive('persist')
            ->withAnyArgs()
            ->andReturn();

        $manager->shouldReceive('flush')
            ->withNoArgs();

        $tm = new ApiTokenManager($registry, $this->hasher());

        $generated = $tm->getOrCreate($user, 'token3');

        self::assertNotSame($token1, $generated->token);
        self::assertNotSame($token2, $generated->token);
        self::assertSame($user, $generated->token->getUser());
        self::assertSame('token3', $generated->token->getName());
        self::assertNotSame('', $generated->plaintext);
    }
}
