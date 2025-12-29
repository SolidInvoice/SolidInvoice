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
use function Zenstruck\Foundry\faker;

/**
 * @group installation
 */
final class InstallationTest extends PantherTestCase
{
    use HasBrowser;

    protected function setUp(): void
    {
        unset(
            $_SERVER['SOLIDINVOICE_LOCALE'],
            $_ENV['SOLIDINVOICE_LOCALE'],
            $_SERVER['SOLIDINVOICE_INSTALLED'],
            $_ENV['SOLIDINVOICE_INSTALLED']
        );

        parent::setUp();

        $configDir = self::getContainer()->getParameter('env(SOLIDINVOICE_CONFIG_DIR)');

        $fs = new Filesystem();
        $fs->exists($configDir) && $fs->remove($configDir);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $configDir = self::getContainer()->getParameter('env(SOLIDINVOICE_CONFIG_DIR)');

        $fs = new Filesystem();
        $fs->exists($configDir) && $fs->remove($configDir);
    }

    public function testItRedirectsToInstallationPage(): void
    {
        $this->pantherBrowser()
            ->visit('/')
            ->assertOn('/install')
            ->use(static fn (Client $client) => $client->quit());
    }

    public function testApplicationInstallationWithSqlite(): void
    {
        $password = faker()->password(minLength: 8);
        $email = faker()->email();
        $firstName = faker()->firstName();
        $lastName = faker()->lastName();

        $this->pantherBrowser()
            ->visit('/install')
            ->assertOn('/install')
            ->use(
                static fn (Client $client) => $client->waitFor('button[name="installation[navigator][next]"]')
            )
            ->assertSee('Welcome to')
            ->click('button[name="installation[navigator][next]"]')
            ->use(
                static fn (Client $client) => $client->waitFor('button[name="installation[navigator][next]"]')
            )
            ->assertSee('System Requirements')
            ->click('button[name="installation[navigator][next]"]')
            ->use(
                static fn (Client $client) => $client->waitFor('input[name="installation[database_config][driver]"][value="sqlite"]')
            )
            ->assertSee('Database Config')
            ->click('label[data-testid="database-driver-sqlite"]')
            ->click('button[name="installation[navigator][next]"]')
            ->use(
                static fn (Client $client) => $client->waitFor('button[name="installation[navigator][next]"]')
            )
            ->assertSee('User Account')
            ->selectFieldOption('installation[user_account][locale]', 'en')
            ->fillField('installation[user_account][firstName]', $firstName)
            ->fillField('installation[user_account][lastName]', $lastName)
            ->fillField('installation[user_account][emailAddress]', $email)
            ->fillField('installation[user_account][password]', $password)
            ->click('button[name="installation[navigator][next]"]')
            ->use(
                static fn (Client $client) => $client->waitFor('button[name="installation[navigator][install]"]')
            )
            ->assertSee('Review')
            ->click('button[name="installation[navigator][install]"]')
            ->use(
                static fn (Client $client) => $client->waitForEnabled('button[name="installation[navigator][next]"]')
            )
            ->click('button[name="installation[navigator][next]"]')
            ->waitUntilSeeIn('h1', 'Installation Complete!')
            ->assertSee('Installation Complete!')
            ->click('button[name="installation[navigator][finish]"]')
            ->assertNotOn('/install')
            ->assertOn('/login')
            ->use(static fn (Client $client) => $client->quit());
    }

    public function testStartPageDisplaysWelcomeInformation(): void
    {
        $this->pantherBrowser()
            ->visit('/install')
            ->assertOn('/install')
            ->use(
                static fn (Client $client) => $client->waitFor('button[name="installation[navigator][next]"]')
            )
            ->assertSee('Welcome to')
            ->assertSee('SolidInvoice')
            ->assertSee('Begin Installation')
            ->assertSee('Professional Invoicing')
            ->assertSee('Online Payments')
            ->assertSee('Client Management')
            ->assertSee('Financial Insights')
            ->assertSeeIn('button[type="submit"]', 'Begin Installation')
            ->use(static fn (Client $client) => $client->quit());
    }

    public function testSystemRequirementsPageDisplaysRequirementChecks(): void
    {
        $this->pantherBrowser()
            ->visit('/install')
            ->assertOn('/install')
            ->use(
                static fn (Client $client) => $client->waitFor('button[name="installation[navigator][next]"]')
            )
            ->click('button[name="installation[navigator][next]"]')
            ->use(
                static fn (Client $client) => $client->waitFor('button[name="installation[navigator][next]"]')
            )
            ->assertSee('System Requirements')
            ->assertSee('Required')
            ->assertSee('Recommended')
            ->assertSee('System Information')
            ->assertSee('PHP Version')
            ->assertSee('Memory Limit')
            ->use(function (Client $client): void {
                // Ensure that the requirements sections are collapsed
                self::assertSame('accordion-collapse collapse hide', $client->getCrawler()->filter('#mandatory-requirements')->attr('class'));
                self::assertSame('accordion-collapse collapse hide', $client->getCrawler()->filter('#optional-requirements')->attr('class'));
            })
            ->assertNotSee('Some requirements were not met')
            ->assertNotSee('.alert-danger')
            ->use(static fn (Client $client) => $client->quit());
    }

    public function testDatabaseConfigPageDisplaysDriverOptions(): void
    {
        $this->pantherBrowser()
            ->visit('/install')
            ->assertOn('/install')
            ->use(
                static fn (Client $client) => $client->waitFor('button[name="installation[navigator][next]"]')
            )
            ->click('button[name="installation[navigator][next]"]')
            ->use(
                static fn (Client $client) => $client->waitFor('button[name="installation[navigator][next]"]')
            )
            ->assertSee('System Requirements')
            ->click('button[name="installation[navigator][next]"]')
            ->use(
                static fn (Client $client) => $client->waitFor('input[name="installation[database_config][driver]"]')
            )
            ->assertSee('Database Config')
            ->assertSee('Choose your database connection')
            ->assertSee('Embedded Database')
            ->assertSeeIn('label[data-testid="database-driver-sqlite"]', 'Recommended')
            ->use(static fn (Client $client) => $client->quit());
    }

    public function testInstallationPreviousButtonNavigatesBack(): void
    {
        $this->pantherBrowser()
            ->visit('/install')
            ->assertOn('/install')
            ->use(
                static fn (Client $client) => $client->waitFor('button[name="installation[navigator][next]"]')
            )
            ->click('button[name="installation[navigator][next]"]')
            ->use(
                static fn (Client $client) => $client->waitFor('button[name="installation[navigator][next]"]')
            )
            ->assertSee('System Requirements')
            ->click('button[name="installation[navigator][next]"]')
            ->use(
                static fn (Client $client) => $client->waitFor('input[name="installation[database_config][driver]"]')
            )
            ->assertSee('Database Config')
            ->click('button[name="installation[navigator][previous]"]')
            ->use(
                static fn (Client $client) => $client->waitFor('button[name="installation[navigator][next]"]')
            )
            ->assertSee('System Requirements')
            ->use(static fn (Client $client) => $client->quit());
    }

    public function testInstallationRequiresDatabaseDriver(): void
    {
        $this->pantherBrowser()
            ->visit('/install')
            ->assertOn('/install')
            ->use(
                static fn (Client $client) => $client->waitFor('button[name="installation[navigator][next]"]')
            )
            ->click('button[name="installation[navigator][next]"]')
            ->use(
                static fn (Client $client) => $client->waitFor('button[name="installation[navigator][next]"]')
            )
            ->assertSee('System Requirements')
            ->click('button[name="installation[navigator][next]"]')
            ->use(
                static fn (Client $client) => $client->waitFor('input[name="installation[database_config][driver]"]')
            )
            ->assertSee('Database Config')
            ->click('button[name="installation[navigator][next]"]')
            ->use(
                static fn (Client $client) => $client->waitFor('.invalid-feedback')
            )
            ->assertSee('Database Config')
            ->assertSee('Please select a database driver')
            ->use(static fn (Client $client) => $client->quit());
    }

    public function testInstallationValidatesRequiredUserFields(): void
    {
        $this->pantherBrowser()
            ->visit('/install')
            ->assertOn('/install')
            ->use(
                static fn (Client $client) => $client->waitFor('button[name="installation[navigator][next]"]')
            )
            ->click('button[name="installation[navigator][next]"]')
            ->use(
                static fn (Client $client) => $client->waitFor('button[name="installation[navigator][next]"]')
            )
            ->assertSee('System Requirements')
            ->click('button[name="installation[navigator][next]"]')
            ->use(
                static fn (Client $client) => $client->waitFor('input[name="installation[database_config][driver]"][value="sqlite"]')
            )
            ->click('label[data-testid="database-driver-sqlite"]')
            ->click('button[name="installation[navigator][next]"]')
            ->use(
                static fn (Client $client) => $client->waitFor('input[name="installation[user_account][firstName]"]')
            )
            ->assertSee('User Account')
            ->click('button[name="installation[navigator][next]"]')
            ->use(
                static fn (Client $client) => $client->waitFor('.invalid-feedback')
            )
            ->assertSee('User Account')
            ->assertSee('Please enter a first name')
            ->use(static fn (Client $client) => $client->quit());
    }

    public function testUserAccountPageDisplaysAllFields(): void
    {
        $this->pantherBrowser()
            ->visit('/install')
            ->assertOn('/install')
            ->use(
                static fn (Client $client) => $client->waitFor('button[name="installation[navigator][next]"]')
            )
            ->click('button[name="installation[navigator][next]"]')
            ->use(
                static fn (Client $client) => $client->waitFor('button[name="installation[navigator][next]"]')
            )
            ->assertSee('System Requirements')
            ->click('button[name="installation[navigator][next]"]')
            ->use(
                static fn (Client $client) => $client->waitFor('input[name="installation[database_config][driver]"][value="sqlite"]')
            )
            ->click('label[data-testid="database-driver-sqlite"]')
            ->click('button[name="installation[navigator][next]"]')
            ->use(
                static fn (Client $client) => $client->waitFor('input[name="installation[user_account][firstName]"]')
            )
            ->assertSee('User Account')
            ->assertSee('Locale')
            ->assertSee('First Name')
            ->assertSee('Last Name')
            ->assertSee('Email')
            ->assertSee('Password')
            ->use(static fn (Client $client) => $client->quit());
    }

    public function testReviewPageDisplaysConfigurationSummary(): void
    {
        $password = faker()->password(minLength: 8);
        $email = faker()->email();
        $firstName = faker()->firstName();
        $lastName = faker()->lastName();

        $this->pantherBrowser()
            ->visit('/install')
            ->assertOn('/install')
            ->use(
                static fn (Client $client) => $client->waitFor('button[name="installation[navigator][next]"]')
            )
            ->click('button[name="installation[navigator][next]"]')
            ->use(
                static fn (Client $client) => $client->waitFor('button[name="installation[navigator][next]"]')
            )
            ->assertSee('System Requirements')
            ->click('button[name="installation[navigator][next]"]')
            ->use(
                static fn (Client $client) => $client->waitFor('input[name="installation[database_config][driver]"][value="sqlite"]')
            )
            ->click('label[data-testid="database-driver-sqlite"]')
            ->click('button[name="installation[navigator][next]"]')
            ->use(
                static fn (Client $client) => $client->waitFor('input[name="installation[user_account][firstName]"]')
            )
            ->selectFieldOption('installation[user_account][locale]', 'en')
            ->fillField('installation[user_account][firstName]', $firstName)
            ->fillField('installation[user_account][lastName]', $lastName)
            ->fillField('installation[user_account][emailAddress]', $email)
            ->fillField('installation[user_account][password]', $password)
            ->click('button[name="installation[navigator][next]"]')
            ->use(
                static fn (Client $client) => $client->waitFor('button[name="installation[navigator][install]"]')
            )
            ->assertSee('Review')
            ->assertSee('Database Configuration')
            ->assertSee('User Account')
            ->assertSee('SQLite')
            ->assertSee($firstName)
            ->assertSee($lastName)
            ->assertSee($email)
            ->use(static fn (Client $client) => $client->quit());
    }

    public function testNavigationBetweenAllSteps(): void
    {
        $password = faker()->password(minLength: 8);
        $email = faker()->email();
        $firstName = faker()->firstName();
        $lastName = faker()->lastName();

        $this->pantherBrowser()
            ->visit('/install')
            ->assertOn('/install')
            ->use(
                static fn (Client $client) => $client->waitFor('button[name="installation[navigator][next]"]')
            )
            ->assertSee('Welcome to')
            ->click('button[name="installation[navigator][next]"]')
            ->use(
                static fn (Client $client) => $client->waitFor('button[name="installation[navigator][next]"]')
            )
            ->assertSee('System Requirements')
            ->click('button[name="installation[navigator][next]"]')
            ->use(
                static fn (Client $client) => $client->waitFor('input[name="installation[database_config][driver]"]')
            )
            ->assertSee('Database Config')
            ->click('label[data-testid="database-driver-sqlite"]')
            ->click('button[name="installation[navigator][next]"]')
            ->use(
                static fn (Client $client) => $client->waitFor('input[name="installation[user_account][firstName]"]')
            )
            ->assertSee('User Account')
            ->selectFieldOption('installation[user_account][locale]', 'en')
            ->fillField('installation[user_account][firstName]', $firstName)
            ->fillField('installation[user_account][lastName]', $lastName)
            ->fillField('installation[user_account][emailAddress]', $email)
            ->fillField('installation[user_account][password]', $password)
            ->click('button[name="installation[navigator][next]"]')
            ->use(
                static fn (Client $client) => $client->waitFor('button[name="installation[navigator][install]"]')
            )
            ->assertSee('Review')
            ->click('button[name="installation[navigator][previous]"]')
            ->use(
                static fn (Client $client) => $client->waitFor('input[name="installation[user_account][firstName]"]')
            )
            ->assertSee('User Account')
            ->click('button[name="installation[navigator][previous]"]')
            ->use(
                static fn (Client $client) => $client->waitFor('input[name="installation[database_config][driver]"]')
            )
            ->assertSee('Database Config')
            ->click('button[name="installation[navigator][previous]"]')
            ->use(
                static fn (Client $client) => $client->waitFor('button[name="installation[navigator][next]"]')
            )
            ->assertSee('System Requirements')
            ->use(static fn (Client $client) => $client->quit());
    }

    public function testInstallationPasswordValidation(): void
    {
        $email = faker()->email();
        $firstName = faker()->firstName();
        $lastName = faker()->lastName();

        $this->pantherBrowser()
            ->visit('/install')
            ->assertOn('/install')
            ->use(
                static fn (Client $client) => $client->waitFor('button[name="installation[navigator][next]"]')
            )
            ->click('button[name="installation[navigator][next]"]')
            ->use(
                static fn (Client $client) => $client->waitFor('button[name="installation[navigator][next]"]')
            )
            ->assertSee('System Requirements')
            ->click('button[name="installation[navigator][next]"]')
            ->use(
                static fn (Client $client) => $client->waitFor('label[data-testid="database-driver-sqlite"]')
            )
            ->click('label[data-testid="database-driver-sqlite"]')
            ->click('button[name="installation[navigator][next]"]')
            ->use(
                static fn (Client $client) => $client->waitFor('input[name="installation[user_account][firstName]"]')
            )
            ->selectFieldOption('installation[user_account][locale]', 'en')
            ->fillField('installation[user_account][firstName]', $firstName)
            ->fillField('installation[user_account][lastName]', $lastName)
            ->fillField('installation[user_account][emailAddress]', $email)
            ->fillField('installation[user_account][password]', 'short')
            ->click('button[name="installation[navigator][next]"]')
            ->use(
                static fn (Client $client) => $client->waitFor('.invalid-feedback')
            )
            ->assertSee('User Account')
            ->assertSee('This value is too short. It should have 6 characters or more.')
            ->use(static fn (Client $client) => $client->quit());
    }

    public function testInstallationEmailValidation(): void
    {
        $password = faker()->password(minLength: 8);
        $firstName = faker()->firstName();
        $lastName = faker()->lastName();

        $this->pantherBrowser()
            ->visit('/install')
            ->assertOn('/install')
            ->use(
                static fn (Client $client) => $client->waitFor('button[name="installation[navigator][next]"]')
            )
            ->click('button[name="installation[navigator][next]"]')
            ->use(
                static fn (Client $client) => $client->waitFor('button[name="installation[navigator][next]"]')
            )
            ->assertSee('System Requirements')
            ->click('button[name="installation[navigator][next]"]')
            ->use(
                static fn (Client $client) => $client->waitFor('input[name="installation[database_config][driver]"][value="sqlite"]')
            )
            ->click('label[data-testid="database-driver-sqlite"]')
            ->click('button[name="installation[navigator][next]"]')
            ->use(
                static fn (Client $client) => $client->waitFor('input[name="installation[user_account][firstName]"]')
            )
            ->selectFieldOption('installation[user_account][locale]', 'en')
            ->fillField('installation[user_account][firstName]', $firstName)
            ->fillField('installation[user_account][lastName]', $lastName)
            ->fillField('installation[user_account][emailAddress]', 'invalid-email')
            ->fillField('installation[user_account][password]', $password)
            ->click('button[name="installation[navigator][next]"]')
            ->use(
                static fn (Client $client) => $client->waitFor('.invalid-feedback')
            )
            ->assertSee('User Account')
            ->assertSee('This value is not a valid email address.')
            ->use(static fn (Client $client) => $client->quit());
    }

    public function testFinishPageDisplaysSuccessInformation(): void
    {
        $password = faker()->password(minLength: 8);
        $email = faker()->email();
        $firstName = faker()->firstName();
        $lastName = faker()->lastName();

        $this->pantherBrowser()
            ->visit('/install')
            ->assertOn('/install')
            ->use(
                static fn (Client $client) => $client->waitFor('button[name="installation[navigator][next]"]')
            )
            ->click('button[name="installation[navigator][next]"]')
            ->use(
                static fn (Client $client) => $client->waitFor('button[name="installation[navigator][next]"]')
            )
            ->assertSee('System Requirements')
            ->click('button[name="installation[navigator][next]"]')
            ->use(
                static fn (Client $client) => $client->waitFor('input[name="installation[database_config][driver]"][value="sqlite"]')
            )
            ->click('label[data-testid="database-driver-sqlite"]')
            ->click('button[name="installation[navigator][next]"]')
            ->use(
                static fn (Client $client) => $client->waitFor('input[name="installation[user_account][firstName]"]')
            )
            ->selectFieldOption('installation[user_account][locale]', 'en')
            ->fillField('installation[user_account][firstName]', $firstName)
            ->fillField('installation[user_account][lastName]', $lastName)
            ->fillField('installation[user_account][emailAddress]', $email)
            ->fillField('installation[user_account][password]', $password)
            ->click('button[name="installation[navigator][next]"]')
            ->use(
                static fn (Client $client) => $client->waitFor('button[name="installation[navigator][install]"]')
            )
            ->click('button[name="installation[navigator][install]"]')
            ->use(
                static fn (Client $client) => $client->waitForEnabled('button[name="installation[navigator][next]"]')
            )
            ->click('button[name="installation[navigator][next]"]')
            ->waitUntilSeeIn('h1', 'Installation Complete!')
            ->assertSee('Installation Complete!')
            ->assertSee("What's Next?")
            ->assertSee('Launch SolidInvoice')
            ->use(static fn (Client $client) => $client->quit());
    }

    public function testInstallationStepDisplaysProgress(): void
    {
        $password = faker()->password(minLength: 8);
        $email = faker()->email();
        $firstName = faker()->firstName();
        $lastName = faker()->lastName();

        $this->pantherBrowser()
            ->visit('/install')
            ->assertOn('/install')
            ->use(
                static fn (Client $client) => $client->waitFor('button[name="installation[navigator][next]"]')
            )
            ->click('button[name="installation[navigator][next]"]')
            ->use(
                static fn (Client $client) => $client->waitFor('button[name="installation[navigator][next]"]')
            )
            ->assertSee('System Requirements')
            ->click('button[name="installation[navigator][next]"]')
            ->use(
                static fn (Client $client) => $client->waitFor('input[name="installation[database_config][driver]"][value="sqlite"]')
            )
            ->click('label[data-testid="database-driver-sqlite"]')
            ->click('button[name="installation[navigator][next]"]')
            ->use(
                static fn (Client $client) => $client->waitFor('input[name="installation[user_account][firstName]"]')
            )
            ->selectFieldOption('installation[user_account][locale]', 'en')
            ->fillField('installation[user_account][firstName]', $firstName)
            ->fillField('installation[user_account][lastName]', $lastName)
            ->fillField('installation[user_account][emailAddress]', $email)
            ->fillField('installation[user_account][password]', $password)
            ->click('button[name="installation[navigator][next]"]')
            ->use(
                static fn (Client $client) => $client->waitFor('button[name="installation[navigator][install]"]')
            )
            ->click('button[name="installation[navigator][install]"]')
            ->use(
                static fn (Client $client) => $client->waitForEnabled('button[name="installation[navigator][next]"]')
            )
            ->assertSee('Generating secret')
            ->assertSee('Creating database')
            ->assertSee('Creating database schema')
            ->assertSee('Creating admin user')
            ->use(static fn (Client $client) => $client->quit());
    }
}
