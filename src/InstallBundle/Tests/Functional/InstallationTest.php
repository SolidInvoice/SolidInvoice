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

namespace SolidInvoice\InstallBundle\Tests\Functional;

use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Panther\Client;
use Symfony\Component\Panther\PantherTestCase;
use function bin2hex;
use function count;
use function file_exists;
use function random_bytes;
use function realpath;
use function unlink;

/**
 * @group installation
 */
class InstallationTest extends PantherTestCase
{
    private bool $hasEnvFile = false;

    protected function setUp(): void
    {
        $fs = new Filesystem();
        $configFile = realpath(static::$defaultOptions['webServerDir'] . '/../') . '/config/env/env.php';
        if ($fs->exists($configFile)) {
            $fs->rename($configFile, $configFile . '.tmp');
            $this->hasEnvFile = true;
        }

        parent::setUp();

        unset($_SERVER['locale'], $_ENV['locale'], $_SERVER['installed'], $_ENV['installed']);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $fs = new Filesystem();

        $configFile = realpath(static::$defaultOptions['webServerDir'] . '/../') . '/config/env/env.php';
        if ($fs->exists($configFile . '.tmp')) {
            $fs->rename($configFile . '.tmp', $configFile);
        }

        if (! $this->hasEnvFile && $fs->exists($configFile)) {
            $fs->remove($configFile);
        }
    }

    public function testItRedirectsToInstallationPage(): void
    {
        $client = self::createPantherClient([
            'env' => [
                'SOLIDINVOICE_ENV' => 'test',
                'SOLIDINVOIE_DEBUG' => '0',
            ],
        ]);

        $crawler = $client->request('GET', '/');

        self::assertStringContainsString('/install', $crawler->getUri());
    }

    public function testApplicationInstallation(): void
    {
        $client = self::createPantherClient([
            'env' => [
                'SOLIDINVOICE_ENV' => 'test',
                'SOLIDINVOIE_DEBUG' => '0',
            ],
        ]);

        $crawler = $client->request('GET', '/install');

        // No error messages on the site
        self::assertCount(0, $crawler->filter('.alert-danger'));

        $this->continue($client, $crawler);

        self::assertStringContainsString('/install/config', $client->getCurrentURL());

        // use a random database name to ensure installation process
        // can create the database and schema
        $dbName = 'test_' . bin2hex(random_bytes(5));

        try {
            // Configuration page

            // use a random database name to ensure installation process
            // can create the database and schema
            $dbName = bin2hex(random_bytes(5));

            $crawler = $client->submitForm(
                'Next',
                [
                    'config_step[database_config][driver]' => 'pdo_mysql',
                    'config_step[database_config][host]' => getenv('database_host') ?: '127.0.0.1',
                    'config_step[database_config][user]' => 'root',
                    'config_step[database_config][password]' => '',
                    'config_step[database_config][name]' => $dbName,
                ]
            );

            self::assertStringContainsString('/install/install', $crawler->getUri());

            $kernel = self::bootKernel();
            self::assertSame($dbName, (function () {
                return $this->getEnv('database_name');
            })(...)->call($kernel->getContainer()));

            // Wait for installation steps to be completed
            $time = microtime(true);
            $client->waitFor('.fa-check.text-success');

            while (2 !== count($crawler->filter('.fa-check.text-success')) && (microtime(true) - $time) < 30) {
                $client->waitFor('.fa-check.text-success');
            }

            self::assertStringNotContainsString('disabled', $crawler->filter('#continue_step')->first()->attr('class'));

            $crawler = $this->continue($client, $crawler);

            self::assertStringContainsString('/install/setup', $client->getCurrentURL());

            $formData = [
                'system_information[locale]' => 'en',
            ];

            if (0 === count($crawler->filter('.callout.callout-warning'))) {
                $formData += [
                    'system_information[username]' => 'admin',
                    'system_information[email_address]' => 'foo@bar.com',
                    'system_information[password][first]' => 'foobar',
                    'system_information[password][second]' => 'foobar',
                ];
            }

            $crawler = $client->submitForm('Next', $formData);

            self::assertStringContainsString('/install/finish', $client->getCurrentURL());
            self::assertStringContainsString('You have successfully installed SolidInvoice!', $crawler->html());
        } finally {
            $configFile = realpath(static::$defaultOptions['webServerDir'] . '/../') . '/config/env/env.php';
            if (file_exists($configFile)) {
                unlink($configFile);
            }

            /** @var ManagerRegistry $doctrine */
            $doctrine = self::getContainer()->get('doctrine');

            /** @var Connection $conn */
            $conn = $doctrine->getConnection();

            try {
                $conn
                    ->createSchemaManager()
                    ->dropDatabase($dbName);
            } catch (\Doctrine\DBAL\Exception $e) {
                // Database doesn't exist
            }
        }
    }

    /**
     * @throws Exception
     */
    private function continue(Client $client, Crawler $crawler): Crawler
    {
        if (0 !== count($crawler->filter('#continue_step'))) {
            return $client->clickLink('Next');
        }

        throw new Exception('Continue button not found');
    }
}
