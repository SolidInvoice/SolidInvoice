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

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Entity\UserInvitation;
use SolidInvoice\UserBundle\Enum\SignupMethod;
use SolidInvoice\UserBundle\Repository\UserInvitationRepository;
use SolidInvoice\UserBundle\Repository\UserRepository;
use SolidInvoice\UserBundle\Test\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use function Zenstruck\Foundry\faker;

#[Group('functional')]
final class RegisterTest extends WebTestCase
{
    use EnsureApplicationInstalled;

    private const string PASSWORD = 'Sup3rStr0ngP@ssw0rd';

    /**
     * @var list<string>
     */
    private array $envOverrides = [];

    /**
     * Default test env: the Turnstile env vars are unset, so the feature is off and registration
     * behaves exactly as before — a valid submission creates a User and logs them in.
     */
    public function testRegistrationSucceedsWhenCaptchaDisabled(): void
    {
        $this->setEnv('SOLIDINVOICE_ALLOW_REGISTRATION', '1');

        $client = $this->bootClient();

        $this->submitRegistration($client, faker()->email());

        self::assertResponseRedirects();
        self::assertSame(1, $this->userCount());

        /** @var ManagerRegistry $registry */
        $registry = self::getContainer()->get('doctrine');
        self::assertSame(SignupMethod::Registration, $registry->getRepository(User::class)->findOneBy([])?->getSignupMethod());
    }

    /**
     * Invitations work with registration closed: the new user joins the inviting company, and the
     * invitation is used up.
     */
    public function testAnInvitedUserJoinsTheInvitingCompany(): void
    {
        $client = $this->bootClient();
        $company = self::getContainer()->get(EntityManagerInterface::class)->find(Company::class, $this->company->getId());
        self::assertInstanceOf(Company::class, $company);
        $invitation = new UserInvitation()
            ->setEmail('invited@acmeplumbing.co.za')
            ->setInvitedBy(UserFactory::createOne(['companies' => [$company]]))
            ->setCompany($company);
        self::getContainer()->get(UserInvitationRepository::class)->save($invitation);
        $invitationId = $invitation->getId();

        $crawler = $client->request(Request::METHOD_GET, '/register?invitation=' . $invitationId);
        self::assertResponseIsSuccessful();
        $client->submit($crawler->filter('form')->form([
            'register[plainPassword]' => self::PASSWORD,
            'register[acceptTerms]' => '1',
        ]));

        self::assertResponseRedirects();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'invited@acmeplumbing.co.za']);
        self::assertInstanceOf(User::class, $user);
        self::assertSame(SignupMethod::Invitation, $user->getSignupMethod());
        self::assertTrue($user->isEnabled());
        self::assertSame([$company->getId()->toString()], $user->getCompanies()->map(static fn (Company $member): string => $member->getId()->toString())->getValues());
        self::assertNull(self::getContainer()->get(UserInvitationRepository::class)->find($invitationId));
    }

    /**
     * When the feature is on but no valid token is submitted (e.g. a bot that never solved the
     * widget), server-side verification fails, the form is invalid, the page re-renders with the
     * captcha error and no User is created.
     */
    public function testRegistrationBlockedWhenCaptchaTokenMissing(): void
    {
        $this->setEnv('SOLIDINVOICE_ALLOW_REGISTRATION', '1');
        $this->setEnv('SOLIDINVOICE_TURNSTILE_SITE_KEY', 'test-site-key');
        $this->setEnv('SOLIDINVOICE_TURNSTILE_SECRET_KEY', 'test-secret-key');

        $client = $this->bootClient();

        $this->submitRegistration($client, faker()->email());

        // An invalid form submission re-renders with Symfony's 422 status (no redirect).
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertStringContainsString('Captcha verification failed', (string) $client->getResponse()->getContent());
        self::assertSame(0, $this->userCount());
    }

    #[After]
    public function resetEnvOverrides(): void
    {
        foreach ($this->envOverrides as $name) {
            unset($_SERVER[$name], $_ENV[$name]);
        }

        $this->envOverrides = [];
    }

    private function setEnv(string $name, string $value): void
    {
        $_SERVER[$name] = $_ENV[$name] = $value;
        $this->envOverrides[] = $name;
    }

    private function submitRegistration(KernelBrowser $client, string $email): void
    {
        $crawler = $client->request(Request::METHOD_GET, '/register');
        self::assertResponseIsSuccessful();

        $form = $crawler->filter('form')->form([
            'register[email]' => $email,
            'register[plainPassword]' => self::PASSWORD,
            'register[acceptTerms]' => '1',
        ]);

        $client->submit($form);
    }

    private function userCount(): int
    {
        /** @var ManagerRegistry $registry */
        $registry = self::getContainer()->get('doctrine');

        return $registry->getRepository(User::class)->count([]);
    }

    private function bootClient(): KernelBrowser
    {
        self::ensureKernelShutdown();
        $client = self::createClient();
        $client->disableReboot();

        return $client;
    }
}
