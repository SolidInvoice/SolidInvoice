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

use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\ApiBundle\ApiTokenManager;
use SolidInvoice\ApiBundle\Test\ApiTestCase;
use SolidInvoice\UserBundle\Entity\ApiToken;
use SolidInvoice\UserBundle\Test\Factory\UserFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[Group('functional')]
final class ApiTokenTest extends ApiTestCase
{
    protected function getResourceClass(): string
    {
        return ApiToken::class;
    }

    public function testCannotCreateViaApi(): void
    {
        // API tokens may only be created through the UI Live Component; the API
        // exposes no write operation, so POST must be rejected as not allowed.
        self::$client->request(Request::METHOD_POST, '/api/profile/api-tokens', [
            'json' => ['name' => 'Test Token', 'description' => 'A test token'],
            'headers' => [
                'content-type' => 'application/ld+json',
                'accept' => 'application/ld+json',
            ],
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_METHOD_NOT_ALLOWED);
    }

    public function testGet(): void
    {
        $token = $this->createToken('My Get Token');
        $uri = '/api/profile/api-tokens/' . $token->getId();

        $result = $this->requestGet($uri);

        self::assertArrayHasKey('id', $result);
        self::assertSame((string) $token->getId(), $result['id']);
        self::assertSame('My Get Token', $result['name']);
        self::assertArrayNotHasKey('token', $result);
    }

    public function testTokenValueNotReturnedOnGet(): void
    {
        $token = $this->createToken('Token No Reveal');
        $uri = '/api/profile/api-tokens/' . $token->getId();

        $result = $this->requestGet($uri);

        self::assertArrayNotHasKey('token', $result, 'The token value must not be returned on GET requests.');
    }

    public function testDelete(): void
    {
        $token = $this->createToken('Token To Delete');
        $uri = '/api/profile/api-tokens/' . $token->getId();

        $this->requestDelete($uri);
    }

    public function testGetCollection(): void
    {
        $this->createToken('Collection Token 1');
        $this->createToken('Collection Token 2');

        $data = $this->requestGetCollection('/api/profile/api-tokens');

        self::assertArraySubset([
            '@context' => $this->getContextForResource(ApiToken::class),
            '@type' => 'Collection',
        ], $data);
    }

    public function testCannotAccessTokenFromDifferentUser(): void
    {
        $secondUser = UserFactory::createOne(['companies' => [$this->company]]);

        /** @var ApiTokenManager $apiTokenManager */
        $apiTokenManager = self::getContainer()->get(ApiTokenManager::class);
        $secondUserToken = $apiTokenManager->create($secondUser, 'Second User Token');

        self::$client->request('GET', '/api/profile/api-tokens/' . $secondUserToken->token->getId()->toString(), [
            'headers' => [
                'content-type' => 'application/ld+json',
                'accept' => 'application/ld+json',
            ],
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    /**
     * Tokens can no longer be created through the API, so functional coverage
     * of the read/delete operations seeds them through the manager instead,
     * owned by the authenticated user so the per-user providers return them.
     */
    private function createToken(string $name, ?string $description = null): ApiToken
    {
        /** @var ApiTokenManager $apiTokenManager */
        $apiTokenManager = self::getContainer()->get(ApiTokenManager::class);

        return $apiTokenManager->create($this->user, $name, $description)
            ->token;
    }
}
