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
use SolidInvoice\ApiBundle\ApiTokenManager;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\UserBundle\Entity\User;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Panther\PantherTestCase;
use function password_hash;

/**
 * @codeCoverageIgnore
 */
abstract class ApiTestCase extends PantherTestCase
{
    use EnsureApplicationInstalled;

    protected static AbstractBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();

        self::$client = static::createClient();

        $registry = self::$kernel->getContainer()->get('doctrine');

        $userRepository = $registry->getRepository(User::class);
        $companyRepository = $registry->getRepository(Company::class);

        /** @var User[] $users */
        $users = $userRepository->findAll();

        /** @var Company[] $companies */
        $companies = $companyRepository->findAll();

        if ([] === $users) {
            $user = new User();
            $user->setUsername('test')
                ->setEmail('test@example.com')
                ->setPassword(password_hash('Password1', PASSWORD_DEFAULT));

            foreach ($companies as $company) {
                $user->addCompany($company);
            }

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
            $server['HTTP_' . strtoupper($key)] = $value;
        }

        self::$client->request(Request::METHOD_POST, $uri, [], [], $server, json_encode($data, JSON_THROW_ON_ERROR));

        $statusCode = self::$client->getResponse()->getStatusCode();
        self::assertSame(201, $statusCode);
        $content = self::$client->getResponse()->getContent();
        self::assertJson($content);

        return json_decode($content, true, 512, JSON_THROW_ON_ERROR);
    }

    protected function requestPut(string $uri, array $data, array $headers = []): array
    {
        $server = ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'];
        foreach ($headers as $key => $value) {
            $server['HTTP_' . strtoupper($key)] = $value;
        }

        self::$client->request(Request::METHOD_PUT, $uri, [], [], $server, json_encode($data, JSON_THROW_ON_ERROR));

        $statusCode = self::$client->getResponse()->getStatusCode();
        self::assertSame(200, $statusCode);
        $content = self::$client->getResponse()->getContent();
        self::assertJson($content);

        return json_decode($content, true, 512, JSON_THROW_ON_ERROR);
    }

    protected function requestGet(string $uri, array $headers = []): array
    {
        $server = ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'];
        foreach ($headers as $key => $value) {
            $server['HTTP_' . strtoupper($key)] = $value;
        }

        self::$client->request(Request::METHOD_GET, $uri, [], [], $server);

        $statusCode = self::$client->getResponse()->getStatusCode();
        self::assertSame(200, $statusCode);
        $content = self::$client->getResponse()->getContent();
        self::assertJson($content);

        return json_decode($content, true, 512, JSON_THROW_ON_ERROR);
    }

    protected function requestDelete(string $uri, array $headers = [])
    {
        $server = ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'];
        foreach ($headers as $key => $value) {
            $server['HTTP_' . strtoupper($key)] = $value;
        }

        self::$client->request(Request::METHOD_DELETE, $uri, [], [], $server);

        $statusCode = self::$client->getResponse()->getStatusCode();
        self::assertSame(204, $statusCode);
        $content = self::$client->getResponse()->getContent();
        self::assertEmpty($content);

        return $content;
    }
}
