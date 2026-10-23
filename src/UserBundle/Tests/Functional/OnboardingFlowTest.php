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

use PHPUnit\Framework\Attributes\Group;
use SensitiveParameter;
use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use SolidInvoice\CoreBundle\Test\Traits\DoctrineTestTrait;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Enum\UserSettingType;
use SolidInvoice\UserBundle\Onboarding\Manager\OnboardingManager;
use SolidInvoice\UserBundle\Repository\UserRepository;
use SolidInvoice\UserBundle\Repository\UserSettingRepository;
use SolidInvoice\UserBundle\Test\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Zenstruck\Browser\Test\HasBrowser;

#[Group('functional')]
final class OnboardingFlowTest extends WebTestCase
{
    use HasBrowser;
    use DoctrineTestTrait;
    use EnsureApplicationInstalled;

    private UserSettingRepository $userSettingRepository;

    protected function setUp(): void
    {
        $this->userSettingRepository = self::getContainer()->get(UserSettingRepository::class);
    }

    public function testOnboardingCreatesTheCompanyAndOpensTheInvoiceEditor(): void
    {
        $user = $this->createUser('test@example.com', 'password');

        $this->browser()
            ->actingAs($user)
            ->visit('/onboarding')
            ->assertSuccessful()
            ->fillField('company_setup_step[companyName]', 'Acme Corporation')
            ->selectFieldOption('company_setup_step[companyCurrency]', 'EUR')
            ->interceptRedirects()
            ->click('Continue to your first invoice')
            ->assertRedirectedTo('/invoices/create')
            ->assertOn('/invoices/create')
            // A company with no clients gets the editor in new-client mode.
            ->assertElementCount('[name="invoice[newClientName]"]', 1)
            ->assertSee('Last step: add your client and send your first invoice.')
        ;

        $user = self::getContainer()->get(UserRepository::class)->find($user->getId());
        self::assertCount(1, $user->getCompanies());
        $company = $user->getCompanies()->first();
        self::assertSame('Acme Corporation', $company->getName());

        $setting = $this->userSettingRepository->findOneBy(['user' => $user, 'key' => UserSettingType::OnboardComplete]);
        self::assertSame('true', $setting?->getValue());
        self::assertNotNull($this->userSettingRepository->findOneBy(['user' => $user, 'key' => UserSettingType::OnboardingCompletedAt]));
    }

    public function testOnboardingRequiresACompanyName(): void
    {
        $user = $this->createUser('blank@example.com', 'password');

        $this->browser()
            ->actingAs($user)
            ->visit('/onboarding')
            ->fillField('company_setup_step[companyName]', '')
            ->click('Continue to your first invoice')
            ->assertOn('/onboarding')
            ->assertStatus(422)
        ;

        self::assertFalse(self::getContainer()->get(OnboardingManager::class)->isOnboardingComplete($user));
    }

    public function testInvitedUserDoesNotSeeOnboarding(): void
    {
        // Create a company first
        $company = CompanyFactory::createOne();

        // Create a user with an existing company (invited user)
        $user = UserFactory::createOne([
            'email' => 'invited@example.com',
            'companies' => [$company],
        ]);

        // Hash the password
        $passwordHasher = self::getContainer()->get(UserPasswordHasherInterface::class);
        $user->setPassword($passwordHasher->hashPassword($user, 'password'));
        $this->em->flush();

        $this->browser()
            ->visit('/login')
            ->assertSuccessful()
            ->fillField('_username', 'invited@example.com')
            ->fillField('_password', 'password')
            ->click('Sign in')
            ->followRedirect()
            // Should go to dashboard, not onboarding
            ->assertOn('/dashboard')
        ;
    }

    public function testNewUserRedirectedToOnboardingAfterLogin(): void
    {
        $this->createUser('newuser@example.com', 'password');

        $this->browser()
            ->visit('/login')
            ->assertSuccessful()
            ->fillField('_username', 'newuser@example.com')
            ->fillField('_password', 'password')
            ->click('Sign in')
            ->followRedirect()
            // Should be redirected to onboarding
            ->assertOn('/onboarding')
            ->assertSee('Company Name')
        ;
    }

    public function testOnboardingRedirectsToDashboardIfAlreadyComplete(): void
    {
        $user = $this->createUser('complete@example.com', 'password');

        // Mark onboarding as complete
        $manager = self::getContainer()->get(OnboardingManager::class);
        $manager->dismissOnboarding($user);

        $this->browser()
            ->actingAs($user)
            ->interceptRedirects()
            ->visit('/onboarding')
            ->assertRedirectedTo('/create-company')
        ;
    }

    private function createUser(string $email, #[SensitiveParameter] string $password): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setEnabled(true);
        $user->setVerified(true);

        $passwordHasher = self::getContainer()->get(UserPasswordHasherInterface::class);
        $user->setPassword($passwordHasher->hashPassword($user, $password));

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }
}
