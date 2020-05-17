<?php

declare(strict_types=1);

/*
 * This file is part of the SolidInvoice project.
 *
 * @author     pierre
 * @copyright  Copyright (c) 2019
 */

namespace SolidInvoice\InstallBundle\Tests\Functional;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception\DriverException;
use Facebook\WebDriver\Exception\TimeoutException;
use SolidInvoice\CoreBundle\ConfigWriter;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Panther\Client;
use Symfony\Component\Panther\PantherTestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * @group installation
 */
class InstallationTest extends PantherTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        $kernel = self::bootKernel();

        $container = $kernel->getContainer();
        $connection = $container->get('doctrine')->getConnection();

        $params = $connection->getParams();

        if (isset($params['master'])) {
            $params = $params['master'];
        }

        $name = $params['path'] ?? $params['dbname'] ?? false;

        if (!$name) {
            return;
        }

        unset($params['dbname'], $params['url']);

        $connection->close();
        $connection = DriverManager::getConnection($params);

        try {
            $connection->getSchemaManager()->dropDatabase($name);
        } catch (DriverException $e) {
            // noop
        }

        if (file_exists($envFile = $kernel->getProjectDir().'/app/config/env.php')) {
            /** @var ConfigWriter $configWriter */
            $configWriter = $container->get(ConfigWriter::class);
            $configWriter->dump(['installed' => null]);
        }
    }

    public function testItRedirectsToInstallationPage()
    {
        $client = self::createPantherClient();

        $crawler = $client->request('GET', '/app.php');

        $this->assertStringContainsString('/install', $crawler->getUri());
    }

    public function testApplicationInstallation()
    {
        $client = self::createPantherClient();

        $crawler = $client->request('GET', '/app.php/install');

        // No error messages on the site
        $this->assertCount(0, $crawler->filter('.alert-danger'));

        $this->continue($client, $crawler);

        $this->assertStringContainsString('/install/config', $client->getCurrentURL());

        // Configuration page
        $crawler = $client->submitForm(
            'Next',
            [
                'config_step[database_config][driver]' => 'pdo_mysql',
                'config_step[database_config][host]' => 'localhost',
                'config_step[database_config][user]' => 'root',
                'config_step[database_config][name]' => 'solidinvoice_test',
                'config_step[email_settings][transport]' => 'sendmail',
            ]
        );

        $this->assertStringContainsString('/install/install', $crawler->getUri());

        $kernel = self::bootKernel();
        $this->assertSame('solidinvoice_test', $kernel->getContainer()->getParameter('env(database_name)'));
        $this->assertSame('sendmail', $kernel->getContainer()->getParameter('env(mailer_transport)'));

        // Wait for installation steps to be completed
        $time = microtime(true);
        $crawler = $client->waitFor('.fa-check.text-success');

        while (2 !== count($crawler->filter('.fa-check.text-success')) && (microtime(true) - $time) < 30) {
            $crawler = $client->waitFor('.fa-check.text-success');
        }

        $this->assertStringNotContainsString('disabled', $crawler->filter('#continue_step')->first()->attr('class'));

        $this->continue($client, $crawler);

        $this->assertStringContainsString('/install/setup', $client->getCurrentURL());

        $crawler = $client->submitForm(
            'Next',
            [
                'system_information[locale]' => 'en',
                'system_information[currency]' => 'USD',
                'system_information[username]' => 'admin',
                'system_information[email_address]' => 'foo@bar.com',
                'system_information[password][first]' => 'foobar',
                'system_information[password][second]' => 'foobar',
            ]
        );

        $this->assertStringContainsString('/install/finish', $crawler->getUri());
        $this->assertStringContainsString('You have successfully installed SolidInvoice!', $crawler->html());
    }

    private function continue(Client $client, Crawler $crawler)
    {
        if (0 !== count($crawler->filter('#continue_step'))) {
            return $client->clickLink('Next');
        }

        throw new \Exception('Continue button not found');
    }
}
