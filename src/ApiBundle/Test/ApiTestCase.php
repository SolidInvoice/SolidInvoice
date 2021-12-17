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

use const PASSWORD_DEFAULT;
use function password_hash;
use SolidInvoice\ApiBundle\ApiTokenManager;
use SolidInvoice\UserBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Panther\PantherTestCase;

/**
 * @codeCoverageIgnore
 */
abstract class ApiTestCase extends PantherTestCase
{
    /**
     * @var KernelBrowser
     */
    protected static $client;

    public function setUp(): void
    {
        parent::setUp();

        self::$client = static::createClient();

        $registry = self::$kernel->getContainer()->get('doctrine');

        $userRepository = $registry->getRepository(User::class);

        /** @var User[] $users */
        $users = $userRepository->findAll();

        if (0 === count($users)) {
            $user = new User();
            $user->setUsername('test')
                ->setEmail('test@example.com')
                ->setPassword(password_hash('Password1', PASSWORD_DEFAULT));
            $registry->getManager()->persist($user);
            $registry->getManager()->flush();
            $users = [$user];
        }

        $tokenManager = new ApiTokenManager($registry);
        $token = $tokenManager->getOrCreate($users[0], 'Functional Test');

        self::$client->setServerParameter('HTTP_X_API_TOKEN', $token->getToken());
    }

    protected function requestPost(string $uri, array $data, array $headers = []): array
    {
        $server = ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'];
        foreach ($headers as $key => $value) {
            $server['HTTP_'.strtoupper($key)] = $value;
        }

        self::$client->request('POST', $uri, [], [], $server, json_encode($data, JSON_THROW_ON_ERROR));

        $statusCode = self::$client->getResponse()->getStatusCode();
        static::assertSame(201, $statusCode);
        $content = self::$client->getResponse()->getContent();
        static::assertJson($content);

        return json_decode($content, true, 512, JSON_THROW_ON_ERROR);
    }

    protected function requestPut(string $uri, array $data, array $headers = []): array
    {
        $server = ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'];
        foreach ($headers as $key => $value) {
            $server['HTTP_'.strtoupper($key)] = $value;
        }

        self::$client->request('PUT', $uri, [], [], $server, json_encode($data, JSON_THROW_ON_ERROR));

        $statusCode = self::$client->getResponse()->getStatusCode();
        static::assertSame(200, $statusCode);
        $content = self::$client->getResponse()->getContent();
        static::assertJson($content);

        return json_decode($content, true, 512, JSON_THROW_ON_ERROR);
    }

    protected function requestGet(string $uri, array $headers = []): array
    {
        $server = ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'];
        foreach ($headers as $key => $value) {
            $server['HTTP_'.strtoupper($key)] = $value;
        }

        self::$client->request('GET', $uri, [], [], $server);

        $statusCode = self::$client->getResponse()->getStatusCode();
        static::assertSame(200, $statusCode);
        $content = self::$client->getResponse()->getContent();
        static::assertJson($content);

        return json_decode($content, true, 512, JSON_THROW_ON_ERROR);
    }

    protected function requestDelete(string $uri, array $headers = [])
    {
        $server = ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'];
        foreach ($headers as $key => $value) {
            $server['HTTP_'.strtoupper($key)] = $value;
        }

        self::$client->request('DELETE', $uri, [], [], $server);

        $statusCode = self::$client->getResponse()->getStatusCode();
        static::assertSame(204, $statusCode);
        $content = self::$client->getResponse()->getContent();
        static::assertEmpty($content);

        return $content;
    }
}
