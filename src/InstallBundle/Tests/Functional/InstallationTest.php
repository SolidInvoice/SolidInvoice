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
use SolidInvoice\CoreBundle\Test\Traits\DatabaseTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Panther\Client;
use Symfony\Component\Panther\PantherTestCase;

/**
 * @group installation
 */
class InstallationTest extends PantherTestCase
{
    use DatabaseTestCase;

    private $disableSchemaUpdate = true;

    public function testItRedirectsToInstallationPage()
    {
        $client = self::createPantherClient();

        $crawler = $client->request('GET', '/');

        static::assertStringContainsString('/install', $crawler->getUri());
    }

    public function testApplicationInstallation()
    {
        $client = self::createPantherClient();

        $crawler = $client->request('GET', '/install');

        // No error messages on the site
        static::assertCount(0, $crawler->filter('.alert-danger'));

        $this->continue($client, $crawler);

        static::assertStringContainsString('/install/config', $client->getCurrentURL());

        // Configuration page
        $crawler = $client->submitForm(
            'Next',
            [
                'config_step[database_config][driver]' => 'pdo_mysql',
                'config_step[database_config][host]' => '127.0.0.1',
                'config_step[database_config][user]' => 'root',
                'config_step[database_config][name]' => 'solidinvoice_test',
            ]
        );

        static::assertStringContainsString('/install/install', $crawler->getUri());

        $kernel = self::bootKernel();
        static::assertSame('solidinvoice_test', $kernel->getContainer()->getParameter('env(database_name)'));

        // Wait for installation steps to be completed
        $time = microtime(true);
        $crawler = $client->waitFor('.fa-check.text-success');

        while (2 !== count($crawler->filter('.fa-check.text-success')) && (microtime(true) - $time) < 30) {
            $crawler = $client->waitFor('.fa-check.text-success');
        }

        static::assertStringNotContainsString('disabled', $crawler->filter('#continue_step')->first()->attr('class'));

        $this->continue($client, $crawler);

        static::assertStringContainsString('/install/setup', $client->getCurrentURL());

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

        static::assertStringContainsString('/install/finish', $crawler->getUri());
        static::assertStringContainsString('You have successfully installed SolidInvoice!', $crawler->html());
    }

    private function continue(Client $client, Crawler $crawler)
    {
        if (0 !== count($crawler->filter('#continue_step'))) {
            return $client->clickLink('Next');
        }

        throw new Exception('Continue button not found');
    }
}
