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

use Exception;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Panther\Client;
use Symfony\Component\Panther\PantherTestCase;
use function count;
use function file_exists;
use function realpath;
use function rename;
use function unlink;

/**
 * @group installation
 */
class InstallationTest extends PantherTestCase
{
    public static function setUpBeforeClass(): void
    {
        $configFile = realpath(static::$defaultOptions['webServerDir'] . '/../') . '/config/env/env.php';
        if (file_exists($configFile)) {
            rename($configFile, $configFile . '.tmp');
        }
    }

    public static function tearDownAfterClass(): void
    {
        $configFile = realpath(static::$defaultOptions['webServerDir'] . '/../') . '/config/env/env.php';
        if (file_exists($configFile . '.tmp')) {
            rename($configFile . '.tmp', $configFile);
        }
    }

    protected function setUp(): void
    {
        unset($_SERVER['locale'], $_ENV['locale'], $_SERVER['installed'], $_ENV['installed']);

        parent::setUp();
    }

    public function testItRedirectsToInstallationPage(): void
    {
        $client = self::createPantherClient(['env' => ['SOLIDINVOICE_ENV' => 'test']]);

        $crawler = $client->request('GET', '/');

        self::assertStringContainsString('/install', $crawler->getUri());
    }

    public function testApplicationInstallation(): void
    {
        $client = self::createPantherClient(['env' => ['SOLIDINVOICE_ENV' => 'test']]);

        $crawler = $client->request('GET', '/install');

        // No error messages on the site
        self::assertCount(0, $crawler->filter('.alert-danger'));

        $this->continue($client, $crawler);

        self::assertStringContainsString('/install/config', $client->getCurrentURL());

        try {
            // Configuration page
            $crawler = $client->submitForm(
                'Next',
                [
                    'config_step[database_config][driver]' => 'pdo_mysql',
                    'config_step[database_config][host]' => getenv('database_host') ?: '127.0.0.1',
                    'config_step[database_config][user]' => 'root',
                    'config_step[database_config][password]' => '',
                    'config_step[database_config][name]' => 'solidinvoice_test',
                ]
            );

            self::assertStringContainsString('/install/install', $crawler->getUri());

            $kernel = self::bootKernel();
            self::assertSame('solidinvoice_test', $kernel->getContainer()->getParameter('env(database_name)'));

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
