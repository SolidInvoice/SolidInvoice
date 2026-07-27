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
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;
use Zenstruck\Foundry\Test\Factories;

/**
 * Proves that the forgot-password reset flow - a third credential-change entry point,
 * alongside profile edit and change-password - is also guarded in demo mode. Without this
 * guard, the shared demo account's password could be changed via a delivered reset email,
 * locking out every other demo visitor.
 */
#[Group('functional')]
final class ForgotPasswordResetDemoModeTest extends WebTestCase
{
    use EnsureApplicationInstalled;
    use Factories;

    private const string EMAIL = 'demo@example.com';

    private const string ORIGINAL_PASSWORD = 'Or1ginalP@ssw0rd';

    private const string NEW_PASSWORD = 'NewSecureP@ssw0rd2024!';

    /**
     * @var list<string>
     */
    private array $envOverrides = [];

    public function testPasswordResetIsIgnoredInDemoModeEvenWithValidToken(): void
    {
        $this->enableDemoMode();

        $client = $this->bootClient();
        $user = $this->createUser(self::EMAIL);
        $originalHash = $user->getPassword();

        $token = $this->generateResetToken($client, $user);

        $client->request(Request::METHOD_GET, '/forgot-password/reset/' . $token);
        self::assertResponseRedirects('/forgot-password/reset');
        $client->followRedirect();
        self::assertResponseIsSuccessful();

        $crawler = $client->getCrawler();
        $form = $crawler->filter('form[name="change_password_form"]')->form();
        $form['change_password_form[plainPassword][first]']->setValue(self::NEW_PASSWORD);
        $form['change_password_form[plainPassword][second]']->setValue(self::NEW_PASSWORD);

        $client->submit($form);

        self::assertResponseRedirects('/login');
        self::assertSame($originalHash, $this->reloadUser($user)->getPassword());
    }

    public function testPasswordResetSucceedsWhenNotInDemoMode(): void
    {
        $client = $this->bootClient();
        $user = $this->createUser('self-hosted@example.com');
        $originalHash = $user->getPassword();

        $token = $this->generateResetToken($client, $user);

        $client->request(Request::METHOD_GET, '/forgot-password/reset/' . $token);
        self::assertResponseRedirects('/forgot-password/reset');
        $client->followRedirect();
        self::assertResponseIsSuccessful();

        $crawler = $client->getCrawler();
        $form = $crawler->filter('form[name="change_password_form"]')->form();
        $form['change_password_form[plainPassword][first]']->setValue(self::NEW_PASSWORD);
        $form['change_password_form[plainPassword][second]']->setValue(self::NEW_PASSWORD);

        $client->submit($form);

        self::assertResponseRedirects('/login');
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
        $this->setEnv('SOLIDINVOICE_DEMO_USERNAME', self::EMAIL);
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

    /**
     * Generates a valid reset token directly via the same helper the "request reset" action
     * uses, bypassing that action entirely (it is itself guarded in demo mode - see
     * Request.php). This isolates the assertion to the Reset action's own guard.
     */
    private function generateResetToken(KernelBrowser $client, User $user): string
    {
        /** @var ResetPasswordHelperInterface $helper */
        $helper = $client->getContainer()->get(ResetPasswordHelperInterface::class);

        return $helper->generateResetToken($user)->getToken();
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
