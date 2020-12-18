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

namespace SolidInvoice\ApiBundle\Test;

use DAMA\DoctrineTestBundle\Doctrine\DBAL\StaticDriver;
use Exception;
use PDOException;
use SolidInvoice\ApiBundle\ApiTokenManager;
use SolidInvoice\UserBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Component\Panther\PantherTestCase;

/**
 * @codeCoverageIgnore
 */
abstract class ApiTestCase extends PantherTestCase
{
    /**
     * @var Client
     */
    protected static $client;

    public function setUp(): void
    {
        parent::setUp();

        if (self::$client) {
            return;
        }

        self::$client = static::createClient();

        $registry = self::$kernel->getContainer()->get('doctrine');

        /** @var User[] $users */
        $users = $registry->getRepository(User::class)->findAll();

        if (0 === count($users)) {
            throw new Exception('No users found!');
        }

        $tokenManager = new ApiTokenManager($registry);
        $token = $tokenManager->getOrCreate($users[0], 'Functional Test');

        try {
            StaticDriver::commit(); // Save user api token
            StaticDriver::beginTransaction();
        } catch (PDOException $e) {
            // noop
        }

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
