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

namespace SolidInvoice\ApiBundle\Test;

use ApiPlatform\JsonLd\ContextBuilderInterface;
use ApiPlatform\Symfony\Bundle\Test\Client;
use Faker\Factory;
use Faker\Generator;
use SolidInvoice\ApiBundle\ApiTokenManager;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\UserBundle\Test\Factory\UserFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use function is_object;

/**
 * @codeCoverageIgnore
 */
abstract class ApiTestCase extends \ApiPlatform\Symfony\Bundle\Test\ApiTestCase
{
    use EnsureApplicationInstalled;

    protected static Client $client;

    protected Generator $faker;

    /**
     * @return class-string
     */
    abstract protected function getResourceClass(): string;

    protected function setUp(): void
    {
        parent::setUp();

        $this->faker = Factory::create();

        $user = UserFactory::createOne(['companies' => [$this->company]])->object();

        $tokenManager = self::getContainer()->get(ApiTokenManager::class);
        $token = $tokenManager->getOrCreate($user, 'Functional Test');

        self::$client = static::createClient(defaultOptions: ['headers' => ['X-API-TOKEN' => $token->getToken()]]);

        // We need to switch the company again,
        // because the ::createClient call resets the container
        // so we lose the state on the CompanySelector service
        static::getContainer()->get(CompanySelector::class)->switchCompany($this->company->getId());
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $headers
     *
     * @return array<string, mixed>
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    protected function requestPost(string $uri, array $data, array $headers = []): array
    {
        $headers = [
            'content-type' => 'application/ld+json',
            'accept' => 'application/ld+json',
            ...$headers
        ];

        $response = self::$client->request(
            method: Request::METHOD_POST,
            url: $uri,
            options: [
                'json' => $data,
                'headers' => $headers,
            ]
        );

        static::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        static::assertResponseFormatSame('jsonld');
        static::assertMatchesResourceItemJsonSchema($this->getResourceClass());

        return $response->toArray(false);
    }

    /**
     * PATCH makes incremental updates to the resource
     *
     * @param array<string, mixed> $data
     * @param array<string, string> $headers
     *
     * @return array<string, mixed>
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    protected function requestPatch(string $uri, array $data, array $headers = []): array
    {
        $headers = [
            'content-type' => 'application/merge-patch+json',
            'accept' => 'application/ld+json',
            ...$headers
        ];

        $response = self::$client->request(
            method: Request::METHOD_PATCH,
            url: $uri,
            options: [
                'json' => $data,
                'headers' => $headers,
            ]
        );

        static::assertResponseStatusCodeSame(Response::HTTP_OK);
        static::assertResponseFormatSame('jsonld');
        static::assertMatchesResourceItemJsonSchema($this->getResourceClass());

        return $response->toArray(false);
    }

    /**
     * PUT replaces the resource entirely
     *
     * @param array<string, mixed> $data
     * @param array<string, string> $headers
     *
     * @return array<string, mixed>
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    protected function requestPut(string $uri, array $data, array $headers = []): array
    {
        $headers = [
            'content-type' => 'application/ld+json',
            'accept' => 'application/ld+json',
            ...$headers
        ];

        $response = self::$client->request(
            method: Request::METHOD_PUT,
            url: $uri,
            options: [
                'json' => $data,
                'headers' => $headers,
            ]
        );

        static::assertResponseStatusCodeSame(Response::HTTP_OK);
        static::assertResponseFormatSame('jsonld');
        static::assertMatchesResourceItemJsonSchema($this->getResourceClass());

        return $response->toArray(false);
    }

    /**
     * @param array<string, string> $headers
     *
     * @return array<mixed>
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    protected function requestGet(string $uri, array $headers = []): array
    {
        $headers = [
            'content-type' => 'application/ld+json',
            'accept' => 'application/ld+json',
            ...$headers
        ];

        $response = self::$client->request(
            method: Request::METHOD_GET,
            url: $uri,
            options: [
                'headers' => $headers,
            ]
        );

        static::assertResponseStatusCodeSame(Response::HTTP_OK);
        static::assertResponseFormatSame('jsonld');
        static::assertMatchesResourceItemJsonSchema($this->getResourceClass());

        return $response->toArray(false);
    }

    /**
     * @param array<string,string> $headers
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    protected function requestDelete(string $uri, array $headers = []): void
    {
        $headers = [
            'content-type' => 'application/ld+json',
            'accept' => 'application/ld+json',
            ...$headers
        ];

        $response = self::$client->request(
            method: Request::METHOD_DELETE,
            url: $uri,
            options: [
                'headers' => $headers,
            ]
        );

        static::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
        self::assertEmpty($response->getContent(false));
    }

    protected function getContextForResource(object|string $resource): string
    {
        /** @var ContextBuilderInterface $contextBuilder */
        $contextBuilder = static::getContainer()->get('api_platform.jsonld.context_builder');

        return $contextBuilder->getResourceContextUri(is_object($resource) ? $resource::class : $resource);
    }
}
