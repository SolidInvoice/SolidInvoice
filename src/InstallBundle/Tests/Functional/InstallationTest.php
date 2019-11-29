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
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Panther\Client;
use Symfony\Component\Panther\PantherTestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * @group installation
 */
class InstallationTest extends PantherTestCase
{
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        $kernel = self::bootKernel();

        $connection = $kernel->getContainer()->get('doctrine')->getConnection();

        $params = $connection->getParams();

        if (isset($params['master'])) {
            $params = $params['master'];
        }

        $name = isset($params['path']) ? $params['path'] : (isset($params['dbname']) ? $params['dbname'] : false);
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

        if (file_exists($parametersFile = $kernel->getProjectDir().'/app/config/parameters.yml')) {
            $parameters = Yaml::parseFile($parametersFile);
            $parameters['parameters']['installed'] = null;
            file_put_contents($parametersFile, Yaml::dump($parameters));
            self::bootKernel(['debug' => true]); // Reboot the kernel with debug to rebuild the cache
        }
    }

    public function testItRedirectsToInstallationPage()
    {
        $client = self::createPantherClient();

        $crawler = $client->request('GET', '/');

        $this->assertContains('/install', $crawler->getUri());
    }

    public function testApplicationInstallation()
    {
        $client = self::createPantherClient();

        $crawler = $client->request('GET', '/install');

        // No error messages on the site
        $this->assertCount(0, $crawler->filter('.alert-danger'));

        echo $crawler->html();
        var_dump($crawler);
        var_dump($client->getCurrentURL());
        var_dump($crawler->getLocation());
        $client->takeScreenshot('screen.png');


        $this->continue($client, $crawler);

        $this->assertContains('/install/config', $client->getCurrentURL());

        // Configuration page
        $crawler = $client->submitForm(
            'Next',
            [
                'config_step[database_config][driver]' => 'pdo_mysql',
                'config_step[database_config][user]' => 'root',
                'config_step[database_config][name]' => 'solidinvoice_test',
                'config_step[email_settings][transport]' => 'sendmail',
            ]
        );

        $this->assertContains('/install/install', $crawler->getUri());

        $kernel = self::bootKernel();
        $this->assertSame($kernel->getContainer()->getParameter('env(database_name)'), 'solidinvoice_test');
        $this->assertSame($kernel->getContainer()->getParameter('env(mailer_transport)'), 'sendmail');

        // Wait for installation steps to be completed
        $time = microtime(true);
        $crawler = $client->waitFor('.fa-check.text-success');

        while (2 !== count($crawler->filter('.fa-check.text-success')) && (microtime(true) - $time) < 30) {
            $crawler = $client->waitFor('.fa-check.text-success');
        }

        $this->assertNotContains('disabled', $crawler->filter('#continue_step')->first()->attr('class'));

        $this->continue($client, $crawler);

        $this->assertContains('/install/setup', $client->getCurrentURL());

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

        $this->assertContains('/install/finish', $crawler->getUri());
        $this->assertContains('You have successfully installed SolidInvoice!', $crawler->html());

        $kernel = self::bootKernel(['debug' => true]);
        $this->assertNotNull($kernel->getContainer()->getParameter('installed'));
    }

    private function continue(Client $client, Crawler $crawler)
    {
        if (0 !== count($crawler->filter('#continue_step'))) {
            return $client->clickLink('Next');
        }

        throw new \Exception('Continue button not found');
    }
}
