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

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Panther\Client;
use Symfony\Component\Panther\PantherTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use function microtime;
use function Zenstruck\Foundry\faker;

/**
 * @group installation
 */
final class InstallationTest extends PantherTestCase
{
    use HasBrowser;

    protected function setUp(): void
    {
        unset($_SERVER['SOLIDINVOICE_LOCALE'], $_ENV['SOLIDINVOICE_LOCALE'], $_SERVER['SOLIDINVOICE_INSTALLED'], $_ENV['SOLIDINVOICE_INSTALLED']);

        parent::setUp();

        $configDir = self::getContainer()->getParameter('env(SOLIDINVOICE_CONFIG_DIR)');

        $fs = new Filesystem();
        $fs->exists($configDir) && $fs->rename($configDir, $configDir . '_test');
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $configDir = self::getContainer()->getParameter('env(SOLIDINVOICE_CONFIG_DIR)');

        $fs = new Filesystem();

        $fs->exists($configDir) && $fs->remove($configDir);
        $fs->exists($configDir . '_test') && $fs->rename($configDir . '_test', $configDir);
    }

    public function testItRedirectsToInstallationPage(): void
    {
        $this->pantherBrowser()
            ->visit('/')
            ->assertOn('/install');
    }

    public function testApplicationInstallation(): void
    {
        $password = faker()->password();

        $this->pantherBrowser()
            ->visit('/install')
            ->assertOn('/install')
            ->assertNotSeeElement('.alert-danger') // No error messages on the site
            ->click('#continue_step')
            ->assertOn('/install/config')
            // ->fillField('config_step[database_config][driver]', 'sqlite') // Default value, no need to set
            ->click('#continue_step')
            ->use(
                function (Client $client): void {
                    $time = microtime(true);
                    do {
                        $client->waitFor('.fa-check.text-success');
                        $crawler = $client->getCrawler();
                    } while (3 !== count($crawler->filter('.fa-check.text-success')) && (microtime(true) - $time) < 30);
                }
            )
            ->click('#continue_step')
            ->assertOn('/install/setup')
            ->fillField('system_information[locale]', 'en')
            ->fillField('system_information[first_name]', faker()->firstName())
            ->fillField('system_information[last_name]', faker()->lastName())
            ->fillField('system_information[email_address]', faker()->email())
            ->fillField('system_information[password][first]', $password)
            ->fillField('system_information[password][second]', $password)
            ->click('#continue_step')
            ->use(function (Client $browser): void {
                $browser->waitFor('.alert-info');
            })
            ->assertOn('/install/finish')
            ->assertSee('You have successfully installed SolidInvoice!');
    }
}
