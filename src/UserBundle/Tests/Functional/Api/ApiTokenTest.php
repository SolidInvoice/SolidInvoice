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

namespace SolidInvoice\UserBundle\Tests\Functional\Api;

use SolidInvoice\ApiBundle\ApiTokenManager;
use SolidInvoice\ApiBundle\Test\ApiTestCase;
use SolidInvoice\UserBundle\Entity\ApiToken;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Test\Factory\UserFactory;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Ulid;
use Zenstruck\Foundry\Test\Factories;

/**
 * @group functional
 */
final class ApiTokenTest extends ApiTestCase
{
    use Factories;

    private User $firstUser;

    protected function getResourceClass(): string
    {
        return ApiToken::class;
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Retrieve the user created by the parent setUp via UserFactory
        // The parent setUp creates one user; we need to track it for cross-user tests
        $this->firstUser = UserFactory::first()->_real();
    }

    public function testCreate(): void
    {
        $data = [
            'name' => 'Test Token',
            'description' => 'A test token',
        ];

        $result = $this->requestPost('/api/profile/api-tokens', $data);

        self::assertArrayHasKey('id', $result);
        self::assertTrue(Ulid::isValid($result['id']));
        self::assertArrayHasKey('token', $result);
        self::assertNotEmpty($result['token']);
        self::assertSame('Test Token', $result['name']);
        self::assertSame('A test token', $result['description']);
    }

    public function testGet(): void
    {
        /** @var ApiTokenManager $apiTokenManager */
        $apiTokenManager = self::getContainer()->get(ApiTokenManager::class);
        $token = $apiTokenManager->create($this->firstUser, 'My Get Token');

        $uri = '/api/profile/api-tokens/' . $token->getId()->toString();
        $result = $this->requestGet($uri);

        self::assertArrayHasKey('id', $result);
        self::assertSame($token->getId()->toString(), $result['id']);
        self::assertSame('My Get Token', $result['name']);
        self::assertArrayNotHasKey('token', $result);
    }

    public function testTokenValueNotReturnedOnGet(): void
    {
        /** @var ApiTokenManager $apiTokenManager */
        $apiTokenManager = self::getContainer()->get(ApiTokenManager::class);
        $token = $apiTokenManager->create($this->firstUser, 'Token No Reveal');

        $uri = '/api/profile/api-tokens/' . $token->getId()->toString();
        $result = $this->requestGet($uri);

        self::assertArrayNotHasKey('token', $result, 'The token value must not be returned on GET requests.');
    }

    public function testDelete(): void
    {
        /** @var ApiTokenManager $apiTokenManager */
        $apiTokenManager = self::getContainer()->get(ApiTokenManager::class);
        $token = $apiTokenManager->create($this->firstUser, 'Token To Delete');

        $uri = '/api/profile/api-tokens/' . $token->getId()->toString();
        $this->requestDelete($uri);
    }

    public function testGetCollection(): void
    {
        /** @var ApiTokenManager $apiTokenManager */
        $apiTokenManager = self::getContainer()->get(ApiTokenManager::class);
        $apiTokenManager->create($this->firstUser, 'Collection Token 1');
        $apiTokenManager->create($this->firstUser, 'Collection Token 2');

        $data = $this->requestGetCollection('/api/profile/api-tokens');

        self::assertArraySubset([
            '@context' => $this->getContextForResource(ApiToken::class),
            '@type' => 'Collection',
        ], $data);
    }

    public function testCannotAccessTokenFromDifferentUser(): void
    {
        $secondUser = UserFactory::createOne(['companies' => [$this->company]])->_real();

        /** @var ApiTokenManager $apiTokenManager */
        $apiTokenManager = self::getContainer()->get(ApiTokenManager::class);
        $secondUserToken = $apiTokenManager->create($secondUser, 'Second User Token');

        $response = self::$client->request('GET', '/api/profile/api-tokens/' . $secondUserToken->getId()->toString(), [
            'headers' => [
                'content-type' => 'application/ld+json',
                'accept' => 'application/ld+json',
            ],
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}
