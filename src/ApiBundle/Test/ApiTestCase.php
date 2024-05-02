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
use DAMA\DoctrineTestBundle\Doctrine\DBAL\StaticDriver;
use JsonException;
use SolidInvoice\ApiBundle\ApiTokenManager;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\UserBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Panther\PantherTestCase;
use function class_exists;
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

        try {
            /** @var KernelBrowser $client */
            self::$client = self::getContainer()->get('test.client');
        } catch (ServiceNotFoundException $e) {
            if (class_exists(KernelBrowser::class)) {
                throw new \LogicException('You cannot create the client used in functional tests if the "framework.test" config is not set to true.');
            }
            throw new \LogicException('You cannot create the client used in functional tests if the BrowserKit component is not available. Try running "composer require symfony/browser-kit"');
        }

        //self::$client = static::createClient();

        $registry = self::getContainer()->get('doctrine');

        $userRepository = $registry->getRepository(User::class);
        $companyRepository = $registry->getRepository(Company::class);

        /** @var User[] $users */
        $users = $userRepository->findAll();

        /** @var Company[] $companies */
        $companies = $companyRepository->findAll();

        //$commit = false;

        if ([] === $users) {
            //$commit = true;
            $user = new User();
            $user->setEmail('test@example.com')
                ->setEnabled(true)
                ->setPassword(password_hash('Password1', PASSWORD_DEFAULT));

            foreach ($companies as $company) {
                $user->addCompany($company);
            }

            $registry->getManager()->persist($user);
            $registry->getManager()->flush();
            $users = [$user];
        }

        static::getContainer()->get(CompanySelector::class)->switchCompany($companies[0]->getId());

        $tokenManager = new ApiTokenManager($registry);
        $token = $tokenManager->getOrCreate($users[0], 'Functional Test');

        self::$client->setServerParameter('HTTP_X_API_TOKEN', $token->getToken());

        /*if ($commit) {
            StaticDriver::commit();
            StaticDriver::beginTransaction();
        }*/
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $headers
     *
     * @return array<string, mixed>
     * @throws JsonException
     */
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

        return json_decode((string) $content, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $headers
     *
     * @return array<string, mixed>
     * @throws JsonException
     */
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

        return json_decode((string) $content, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @param array<string, string> $headers
     *
     * @return array<mixed>
     * @throws JsonException
     */
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

        return json_decode((string) $content, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @param array<string,string> $headers
     */
    protected function requestDelete(string $uri, array $headers = []): string
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
