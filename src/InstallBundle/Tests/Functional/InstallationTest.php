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
use Symfony\Bundle\FrameworkBundle\Secrets\SodiumVault;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Panther\Client;
use Symfony\Component\Panther\PantherTestCase;
use function assert;
use function count;
use function getenv;
use function microtime;
use function Zenstruck\Foundry\faker;

/**
 * @group installation
 */
final class InstallationTest extends PantherTestCase
{
    /**
     * @var array<string, string>
     */
    private array $configValues = [];

    protected function setUp(): void
    {
        unset($_SERVER['SOLIDINVOICE_LOCALE'], $_ENV['SOLIDINVOICE_LOCALE'], $_SERVER['SOLIDINVOICE_INSTALLED'], $_ENV['SOLIDINVOICE_INSTALLED']);

        parent::setUp();

        $vault = self::getContainer()->get('secrets.vault');
        assert($vault instanceof SodiumVault);

        $this->configValues = $vault->list(true);

        foreach ($this->configValues as $key => $value) {
            $vault->remove($key);
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $vault = self::getContainer()->get('secrets.vault');
        assert($vault instanceof SodiumVault);

        foreach ($this->configValues as $key => $value) {
            $vault->seal($key, $value);
        }
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

        // Configuration page
        $crawler = $client->submitForm(
            'Next',
            [
                'config_step[database_config][driver]' => getenv('SOLIDINVOICE_DATABASE_DRIVER') ?: 'pdo_mysql',
                'config_step[database_config][host]' => getenv('SOLIDINVOICE_DATABASE_HOST') ?: '127.0.0.1',
                'config_step[database_config][user]' => getenv('SOLIDINVOICE_DATABASE_USER') ?: 'root',
                'config_step[database_config][password]' => getenv('SOLIDINVOICE_DATABASE_PASSWORD') ?: '',
                'config_step[database_config][name]' => 'solidinvoice_install_test',
            ]
        );

        self::assertStringContainsString('/install/install', $crawler->getUri());

        $kernel = self::bootKernel();
        self::assertSame('solidinvoice_test', $kernel->getContainer()->getParameter('env(database_name)'));

        // Wait for installation steps to be completed
        $time = microtime(true);
        $client->waitFor('.fa-check.text-success');

        while (3 !== count($crawler->filter('.fa-check.text-success')) && (microtime(true) - $time) < 30) {
            $client->waitFor('.fa-check.text-success');
        }

        self::assertStringNotContainsString('disabled', $crawler->filter('#continue_step')->first()->attr('class'));

        $crawler = $this->continue($client, $crawler);

        self::assertStringContainsString('/install/setup', $client->getCurrentURL());

        $formData = [
            'system_information[locale]' => 'en',
        ];

        if (0 === count($crawler->filter('.callout.callout-warning'))) {
            $password = faker()->password();

            $formData += [
                'system_information[email_address]' => faker()->email(),
                'system_information[password][first]' => $password,
                'system_information[password][second]' => $password,
            ];
        }

        $crawler = $client->submitForm('Next', $formData);

        self::assertStringContainsString('/install/finish', $client->getCurrentURL());
        self::assertStringContainsString('You have successfully installed SolidInvoice!', $crawler->html());
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
