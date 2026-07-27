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

namespace SolidInvoice\UserBundle\Tests\Functional;

use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Test\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Zenstruck\Foundry\Test\Factories;

/**
 * Locks the shared demo account's email + password against change, so no visitor can lock others
 * out of the demo. In demo mode the fields are disabled server-side (via ModeResolver::allows()),
 * not just hidden in the UI, so even a crafted POST that supplies values for the disabled fields
 * cannot change them.
 */
#[Group('functional')]
final class EditProfileTest extends WebTestCase
{
    use EnsureApplicationInstalled;
    use Factories;

    private const string ORIGINAL_EMAIL = 'demo@example.com';

    private const string ORIGINAL_PASSWORD = 'Or1ginalP@ssw0rd';

    private const string NEW_PASSWORD = 'NewSecureP@ssw0rd2024!';

    /**
     * @var list<string>
     */
    private array $envOverrides = [];

    public function testEmailAndCurrentPasswordFieldsAreDisabledInDemoMode(): void
    {
        $this->enableDemoMode();

        $client = $this->bootClient();
        $user = $this->createUser(self::ORIGINAL_EMAIL);
        $client->loginUser($user);

        $crawler = $client->request(Request::METHOD_GET, '/profile/edit');

        self::assertResponseIsSuccessful();
        self::assertNotNull($crawler->filter('#profile_email')->attr('disabled'));
        self::assertNotNull($crawler->filter('#profile_current_password')->attr('disabled'));
    }

    public function testEmailChangeIsIgnoredInDemoModeEvenWithCraftedPost(): void
    {
        $this->enableDemoMode();

        $client = $this->bootClient();
        $user = $this->createUser(self::ORIGINAL_EMAIL);
        $client->loginUser($user);

        $crawler = $client->request(Request::METHOD_GET, '/profile/edit');
        self::assertResponseIsSuccessful();

        $form = $crawler->filter('form[name="profile"]')->form([
            'profile[firstName]' => 'Demo',
            'profile[lastName]' => 'User',
        ]);

        // DomCrawler does not enforce the HTML `disabled` attribute the way a real browser would,
        // so this simulates a crafted POST that supplies a value for a server-side disabled field.
        $form['profile[email]']->setValue('attacker@example.com');

        $client->submit($form);

        self::assertSame(self::ORIGINAL_EMAIL, $this->reloadUser($user)->getEmail());
    }

    public function testPasswordChangeIsIgnoredInDemoModeEvenWithCraftedPost(): void
    {
        $this->enableDemoMode();

        $client = $this->bootClient();
        $user = $this->createUser(self::ORIGINAL_EMAIL);
        $client->loginUser($user);

        $originalHash = $user->getPassword();

        $crawler = $client->request(Request::METHOD_GET, '/profile/change-password');
        self::assertResponseIsSuccessful();

        $form = $crawler->filter('form[name="change_password"]')->form();
        $form['change_password[currentPassword]']->setValue(self::ORIGINAL_PASSWORD);
        $form['change_password[plainPassword][first]']->setValue(self::NEW_PASSWORD);
        $form['change_password[plainPassword][second]']->setValue(self::NEW_PASSWORD);

        $client->submit($form);

        self::assertSame($originalHash, $this->reloadUser($user)->getPassword());
    }

    public function testEmailChangeSucceedsWhenNotInDemoMode(): void
    {
        $client = $this->bootClient();
        $user = $this->createUser('self-hosted@example.com');
        $client->loginUser($user);

        $crawler = $client->request(Request::METHOD_GET, '/profile/edit');
        self::assertResponseIsSuccessful();

        self::assertNull($crawler->filter('#profile_email')->attr('disabled'));
        self::assertNull($crawler->filter('#profile_current_password')->attr('disabled'));

        $form = $crawler->filter('form[name="profile"]')->form([
            'profile[firstName]' => 'Demo',
            'profile[lastName]' => 'User',
            'profile[email]' => 'changed@example.com',
            'profile[current_password]' => self::ORIGINAL_PASSWORD,
        ]);

        $client->submit($form);

        self::assertResponseRedirects('/profile');
        self::assertSame('changed@example.com', $this->reloadUser($user)->getEmail());
    }

    public function testPasswordChangeSucceedsWhenNotInDemoMode(): void
    {
        $client = $this->bootClient();
        $user = $this->createUser('self-hosted@example.com');
        $client->loginUser($user);

        $originalHash = $user->getPassword();

        $crawler = $client->request(Request::METHOD_GET, '/profile/change-password');
        self::assertResponseIsSuccessful();

        $form = $crawler->filter('form[name="change_password"]')->form();
        $form['change_password[currentPassword]']->setValue(self::ORIGINAL_PASSWORD);
        $form['change_password[plainPassword][first]']->setValue(self::NEW_PASSWORD);
        $form['change_password[plainPassword][second]']->setValue(self::NEW_PASSWORD);

        $client->submit($form);

        self::assertResponseRedirects('/profile');
        self::assertNotSame($originalHash, $this->reloadUser($user)->getPassword());
    }

    #[After]
    public function resetEnvOverrides(): void
    {
        foreach ($this->envOverrides as $name) {
            unset($_SERVER[$name], $_ENV[$name]);
        }

        $this->envOverrides = [];
    }

    private function enableDemoMode(): void
    {
        $this->setEnv('SOLIDINVOICE_MODE', 'demo');
        $this->setEnv('SOLIDINVOICE_DEMO_USERNAME', self::ORIGINAL_EMAIL);
        $this->setEnv('SOLIDINVOICE_DEMO_PASSWORD', self::ORIGINAL_PASSWORD);
    }

    private function setEnv(string $name, string $value): void
    {
        $_SERVER[$name] = $_ENV[$name] = $value;
        $this->envOverrides[] = $name;
    }

    private function createUser(string $email): User
    {
        $passwordHasher = self::getContainer()->get(UserPasswordHasherInterface::class);

        return UserFactory::createOne([
            'companies' => [$this->company],
            'email' => $email,
            'password' => $passwordHasher->hashPassword(new User(), self::ORIGINAL_PASSWORD),
            'enabled' => true,
            'verified' => true,
        ])->_real();
    }

    private function reloadUser(User $user): User
    {
        /** @var ManagerRegistry $registry */
        $registry = self::getContainer()->get('doctrine');
        $manager = $registry->getManager();
        $manager->clear();

        $reloaded = $registry->getRepository(User::class)->find($user->getId());
        self::assertInstanceOf(User::class, $reloaded);

        return $reloaded;
    }

    private function bootClient(): KernelBrowser
    {
        self::ensureKernelShutdown();
        $client = self::createClient();
        $client->disableReboot();

        return $client;
    }
}
