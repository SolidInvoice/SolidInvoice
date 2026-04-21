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
use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\AppRequirements;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Panther\Client;
use Symfony\Component\Panther\PantherTestCase;
use Zenstruck\Browser\PantherBrowser;
use Zenstruck\Browser\Test\HasBrowser;
use function Zenstruck\Foundry\faker;

#[Group('installation')]
final class InstallationTest extends PantherTestCase
{
    use HasBrowser;

    private PantherBrowser $browser;

    protected function setUp(): void
    {
        unset(
            $_SERVER['SOLIDINVOICE_LOCALE'],
            $_ENV['SOLIDINVOICE_LOCALE'],
            $_SERVER['SOLIDINVOICE_INSTALLED'],
            $_ENV['SOLIDINVOICE_INSTALLED']
        );

        $configDir = self::getContainer()->getParameter('env(SOLIDINVOICE_CONFIG_DIR)');

        // Remove the config directory BEFORE parent::setUp() to ensure a clean state
        // when the kernel boots. This prevents any cached secrets from affecting the test.
        $fs = new Filesystem();
        $fs->exists($configDir) && $fs->remove($configDir);

        parent::setUp();

        // Clear users and companies tables to ensure installation test starts fresh.
        // Other tests may have created data that persists due to Panther using
        // a real server process with its own database connection.
        /** @var Connection $connection */
        $connection = self::getContainer()->get('doctrine')->getConnection();
        $connection->executeStatement('DELETE FROM users');
        $connection->executeStatement('DELETE FROM companies');

        $this->browser = $this->pantherBrowser();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if ($this->status()->isFailure() || $this->status()->isError()) {
            $this->browser->takeScreenshot($this->toString() . '.png');
        }

        $this->browser->use(
            static fn (Client $client) => $client->getCookieJar()->clear()
        );

        $configDir = self::getContainer()->getParameter('env(SOLIDINVOICE_CONFIG_DIR)');

        $fs = new Filesystem();
        $fs->exists($configDir) && $fs->remove($configDir);

        unset($this->browser);
    }

    public function testSystemRequirements(): void
    {
        $req = new AppRequirements(
            self::getContainer()->getParameter('env(SOLIDINVOICE_CONFIG_DIR)'),
            self::getContainer()->getParameter('kernel.cache_dir'),
            self::getContainer()->getParameter('kernel.logs_dir'),
        );

        self::assertSame([], $req->getFailedRequirements());
    }

    public function testItRedirectsToInstallationPage(): void
    {
        $this->browser
            ->visit('/')
            ->assertOn('/install');
    }

    public function testApplicationInstallationWithSqlite(): void
    {
        $password = faker()->password(minLength: 8);
        $email = faker()->email();
        $firstName = faker()->firstName();
        $lastName = faker()->lastName();

        $this->browser
            ->visit('/install')
            ->assertOn('/install')
            ->assertSee('Welcome to')
            ->click('button[name="installation[navigator][next]"]')
            ->waitUntilSeeIn('h4', 'System requirements')
            ->assertNotSee('Some requirements were not met')
            ->assertNotSeeElement('.alert-danger')
            ->click('button[name="installation[navigator][next]"]')
            ->use(
                static fn (Client $client) => $client->waitFor('input[name="installation[database_config][driver]"][value="sqlite"]')
            )
            ->assertSee('Database Config')
            ->click('label[data-testid="database-driver-sqlite"]')
            ->wait(200)
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
            ->waitUntilSeeIn('h2', 'Login to your account')
            ->assertOn('/login');
    }

    public function testStartPageDisplaysWelcomeInformation(): void
    {
        $this->browser
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
            ->assertSeeIn('button[type="submit"]', 'Begin Installation');
    }

    public function testSystemRequirementsPageDisplaysRequirementChecks(): void
    {
        $this->browser
            ->visit('/install')
            ->assertOn('/install')
            ->click('button[name="installation[navigator][next]"]')
            ->waitUntilSeeIn('h4', 'System requirements')
            ->assertSee('Required')
            ->assertSee('Recommended')
            ->assertSee('System Information')
            ->assertSee('PHP Version')
            ->assertSee('Memory Limit')
            ->use(function (Client $client): void {
                // Ensure that the requirements sections are collapsed
                self::assertSame('accordion-collapse collapse', $client->getCrawler()->filter('#mandatory-requirements')->attr('class'));
                self::assertSame('accordion-collapse collapse', $client->getCrawler()->filter('#optional-requirements')->attr('class'));
            })
            ->assertNotSee('Some requirements were not met')
            ->assertNotSeeElement('.alert-danger');
    }

    public function testDatabaseConfigPageDisplaysDriverOptions(): void
    {
        $this->browser
            ->visit('/install')
            ->assertOn('/install')
            ->click('button[name="installation[navigator][next]"]')
            ->waitUntilSeeIn('h4', 'System requirements')
            ->assertNotSee('Some requirements were not met')
            ->assertNotSeeElement('.alert-danger')
            ->click('button[name="installation[navigator][next]"]')
            ->use(
                static fn (Client $client) => $client->waitFor('input[name="installation[database_config][driver]"]')
            )
            ->assertSee('Database Config')
            ->assertSee('Choose your database connection')
            ->assertSee('Embedded Database')
            ->assertSeeIn('label[data-testid="database-driver-sqlite"]', 'Recommended');
    }

    public function testInstallationPreviousButtonNavigatesBack(): void
    {
        $this->browser
            ->visit('/install')
            ->assertOn('/install')
            ->click('button[name="installation[navigator][next]"]')
            ->waitUntilSeeIn('h4', 'System requirements')
            ->assertNotSee('Some requirements were not met')
            ->assertNotSeeElement('.alert-danger')
            ->click('button[name="installation[navigator][next]"]')
            ->use(
                static fn (Client $client) => $client->waitFor('input[name="installation[database_config][driver]"]')
            )
            ->assertSee('Database Config')
            ->click('button[name="installation[navigator][previous]"]')
            ->waitUntilSeeIn('h4', 'System requirements')
            ->assertNotSee('Some requirements were not met')
            ->assertNotSeeElement('.alert-danger')
        ;
    }

    public function testInstallationRequiresDatabaseDriver(): void
    {
        $this->browser
            ->visit('/install')
            ->assertOn('/install')
            ->click('button[name="installation[navigator][next]"]')
            ->waitUntilSeeIn('h4', 'System requirements')
            ->assertNotSee('Some requirements were not met')
            ->assertNotSeeElement('.alert-danger')
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
            ->assertSee('Please select a database driver');
    }

    public function testInstallationValidatesRequiredUserFields(): void
    {
        $this->browser
            ->visit('/install')
            ->assertOn('/install')
            ->click('button[name="installation[navigator][next]"]')
            ->waitUntilSeeIn('h4', 'System requirements')
            ->assertNotSee('Some requirements were not met')
            ->assertNotSeeElement('.alert-danger')
            ->click('button[name="installation[navigator][next]"]')
            ->use(
                static fn (Client $client) => $client->waitFor('input[name="installation[database_config][driver]"][value="sqlite"]')
            )
            ->click('label[data-testid="database-driver-sqlite"]')
            ->wait(200)
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
            ->assertSee('Please enter a first name');
    }

    public function testUserAccountPageDisplaysAllFields(): void
    {
        $this->browser
            ->visit('/install')
            ->assertOn('/install')
            ->click('button[name="installation[navigator][next]"]')
            ->waitUntilSeeIn('h4', 'System requirements')
            ->assertNotSee('Some requirements were not met')
            ->assertNotSeeElement('.alert-danger')
            ->click('button[name="installation[navigator][next]"]')
            ->use(
                static fn (Client $client) => $client->waitFor('input[name="installation[database_config][driver]"][value="sqlite"]')
            )
            ->click('label[data-testid="database-driver-sqlite"]')
            ->wait(200)
            ->click('button[name="installation[navigator][next]"]')
            ->use(
                static fn (Client $client) => $client->waitFor('input[name="installation[user_account][firstName]"]')
            )
            ->assertSee('User Account')
            ->assertSee('Application URL')
            ->assertSee('Locale')
            ->assertSee('First Name')
            ->assertSee('Last Name')
            ->assertSee('Email')
            ->assertSee('Password');
    }

    public function testReviewPageDisplaysConfigurationSummary(): void
    {
        $password = faker()->password(minLength: 8);
        $email = faker()->email();
        $firstName = faker()->firstName();
        $lastName = faker()->lastName();

        $this->browser
            ->visit('/install')
            ->assertOn('/install')
            ->click('button[name="installation[navigator][next]"]')
            ->waitUntilSeeIn('h4', 'System requirements')
            ->assertNotSee('Some requirements were not met')
            ->assertNotSeeElement('.alert-danger')
            ->click('button[name="installation[navigator][next]"]')
            ->use(
                static fn (Client $client) => $client->waitFor('input[name="installation[database_config][driver]"][value="sqlite"]')
            )
            ->click('label[data-testid="database-driver-sqlite"]')
            ->wait(200)
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
            ->assertSee($email);
    }

    public function testNavigationBetweenAllSteps(): void
    {
        $password = faker()->password(minLength: 8);
        $email = faker()->email();
        $firstName = faker()->firstName();
        $lastName = faker()->lastName();

        $this->browser
            ->visit('/install')
            ->assertOn('/install')
            ->assertSee('Welcome to')
            ->click('button[name="installation[navigator][next]"]')
            ->waitUntilSeeIn('h4', 'System requirements')
            ->assertNotSee('Some requirements were not met')
            ->assertNotSeeElement('.alert-danger')
            ->click('button[name="installation[navigator][next]"]')
            ->use(
                static fn (Client $client) => $client->waitFor('input[name="installation[database_config][driver]"]')
            )
            ->assertSee('Database Config')
            ->click('label[data-testid="database-driver-sqlite"]')
            ->wait(200)
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
            ->waitUntilSeeIn('h4', 'System requirements')
            ->assertNotSee('Some requirements were not met')
            ->assertNotSeeElement('.alert-danger')
        ;
    }

    public function testInstallationPasswordValidation(): void
    {
        $email = faker()->email();
        $firstName = faker()->firstName();
        $lastName = faker()->lastName();

        $this->browser
            ->visit('/install')
            ->assertOn('/install')
            ->click('button[name="installation[navigator][next]"]')
            ->waitUntilSeeIn('h4', 'System requirements')
            ->assertNotSee('Some requirements were not met')
            ->assertNotSeeElement('.alert-danger')
            ->click('button[name="installation[navigator][next]"]')
            ->use(
                static fn (Client $client) => $client->waitFor('label[data-testid="database-driver-sqlite"]')
            )
            ->click('label[data-testid="database-driver-sqlite"]')
            ->wait(200)
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
            ->assertSee('This value is too short. It should have 6 characters or more.');
    }

    public function testInstallationEmailValidation(): void
    {
        $password = faker()->password(minLength: 8);
        $firstName = faker()->firstName();
        $lastName = faker()->lastName();

        $this->browser
            ->visit('/install')
            ->assertOn('/install')
            ->click('button[name="installation[navigator][next]"]')
            ->waitUntilSeeIn('h4', 'System requirements')
            ->assertNotSee('Some requirements were not met')
            ->assertNotSeeElement('.alert-danger')
            ->click('button[name="installation[navigator][next]"]')
            ->use(
                static fn (Client $client) => $client->waitFor('input[name="installation[database_config][driver]"][value="sqlite"]')
            )
            ->click('label[data-testid="database-driver-sqlite"]')
            ->wait(200)
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
            ->assertSee('This value is not a valid email address.');
    }

    public function testFinishPageDisplaysSuccessInformation(): void
    {
        $password = faker()->password(minLength: 8);
        $email = faker()->email();
        $firstName = faker()->firstName();
        $lastName = faker()->lastName();

        $this->browser
            ->visit('/install')
            ->assertOn('/install')
            ->click('button[name="installation[navigator][next]"]')
            ->waitUntilSeeIn('h4', 'System requirements')
            ->assertNotSee('Some requirements were not met')
            ->assertNotSeeElement('.alert-danger')
            ->click('button[name="installation[navigator][next]"]')
            ->use(
                static fn (Client $client) => $client->waitFor('input[name="installation[database_config][driver]"][value="sqlite"]')
            )
            ->click('label[data-testid="database-driver-sqlite"]')
            ->wait(200)
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
            ->assertSee('Launch SolidInvoice');
    }

    public function testInstallationStepDisplaysProgress(): void
    {
        $password = faker()->password(minLength: 8);
        $email = faker()->email();
        $firstName = faker()->firstName();
        $lastName = faker()->lastName();

        $this->browser
            ->visit('/install')
            ->assertOn('/install')
            ->click('button[name="installation[navigator][next]"]')
            ->waitUntilSeeIn('h4', 'System requirements')
            ->assertNotSee('Some requirements were not met')
            ->assertNotSeeElement('.alert-danger')
            ->click('button[name="installation[navigator][next]"]')
            ->use(
                static fn (Client $client) => $client->waitFor('input[name="installation[database_config][driver]"][value="sqlite"]')
            )
            ->click('label[data-testid="database-driver-sqlite"]')
            ->wait(200)
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
            ->assertSee('Creating admin user');
    }
}
