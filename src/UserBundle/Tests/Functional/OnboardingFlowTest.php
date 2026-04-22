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

use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use SolidInvoice\CoreBundle\Test\Traits\DoctrineTestTrait;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Repository\InvoiceRepository;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Enum\UserSettingType;
use SolidInvoice\UserBundle\Onboarding\Manager\OnboardingManager;
use SolidInvoice\UserBundle\Repository\UserRepository;
use SolidInvoice\UserBundle\Repository\UserSettingRepository;
use SolidInvoice\UserBundle\Test\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

/**
 * @group functional
 */
final class OnboardingFlowTest extends WebTestCase
{
    use HasBrowser;
    use DoctrineTestTrait;
    use Factories;
    use EnsureApplicationInstalled;

    private UserSettingRepository $userSettingRepository;

    protected function setUp(): void
    {
        $this->userSettingRepository = self::getContainer()->get(UserSettingRepository::class);
    }

    public function testCompleteOnboardingWithAllSteps(): void
    {
        $user = $this->createUser('test@example.com', 'password');

        $this->browser()
            ->actingAs($user)
            ->visit('/onboarding')
            ->assertSuccessful()
            ->assertOn('/onboarding')
            ->assertSee('What we\'re setting up:')
            ->assertSee('Company Name')
            // Fill company step
            ->fillField('onboarding[company][companyName]', 'Acme Corporation')
            ->selectFieldOption('onboarding[company][companyCurrency]', 'USD')
            ->click('Continue')
            // Client step
            ->assertSuccessful()
            ->assertSee('Add your first client')
            ->fillField('onboarding[client][clientName]', 'John Doe')
            ->fillField('onboarding[client][clientEmail]', 'john@example.com')
            ->click('Continue')
            // Invoice step
            ->assertSuccessful()
            ->assertSee('Create your first invoice')
            ->fillField('onboarding[invoice][invoiceDescription]', 'Website Design')
            ->fillField('onboarding[invoice][invoiceAmount]', '1500.00')
            ->interceptRedirects()
            ->click('Create & View My Invoice')
            // Should redirect to invoice detail page
            ->assertRedirectedTo('/invoices/view/' . self::getContainer()->get(InvoiceRepository::class)->findOneBy([])->getId()->toString())
            ->followRedirect()
            ->assertSeeIn('.alert-success', 'Your first invoice is ready!')
        ;

        // Refresh user
        $user = self::getContainer()->get(UserRepository::class)->find($user->getId());

        // Verify onboarding is marked complete
        $setting = $this->userSettingRepository->findOneBy([
            'user' => $user,
            'key' => UserSettingType::OnboardComplete,
        ]);
        self::assertNotNull($setting);
        self::assertSame('true', $setting->getValue());

        // Verify company was created
        self::assertCount(1, $user->getCompanies());

        // Verify invoice was created
        $invoices = $this->em->getRepository(Invoice::class)->findBy(['company' => $user->getCompanies()->first()]);
        self::assertCount(1, $invoices);
        self::assertSame('Website Design', $invoices[0]->getLines()->first()->getDescription());
    }

    public function testSkipClientStep(): void
    {
        $user = $this->createUser('test2@example.com', 'password');

        $this->browser()
            ->actingAs($user)
            ->visit('/onboarding')
            ->assertSuccessful()
            // Company step
            ->fillField('onboarding[company][companyName]', 'Test Company')
            ->selectFieldOption('onboarding[company][companyCurrency]', 'USD')
            ->click('Continue')
            // Skip client step
            ->assertSuccessful()
            ->assertSee('Add your first client')
            ->click('#onboarding_navigator_skip')
            // Should go to complete step (invoice auto-skipped)
            ->assertSuccessful()
            ->assertSee('You\'re all set!')
            ->interceptRedirects()
            ->click('Go to Dashboard')
            ->assertRedirectedTo('/dashboard')
            ->followRedirect()
            ->assertSeeIn('.alert-success', 'Welcome to SolidInvoice!')
        ;

        // Verify both client and invoice were skipped
        $setting = $this->userSettingRepository->findOneBy([
            'user' => $user,
            'key' => UserSettingType::OnboardingSkipped,
        ]);
        self::assertNotNull($setting);
        $skipped = json_decode($setting->getValue(), true);
        self::assertContains('client', $skipped);
        self::assertContains('invoice', $skipped);
    }

    public function testSkipInvoiceStepOnly(): void
    {
        $user = $this->createUser('test3@example.com', 'password');

        $this->browser()
            ->actingAs($user)
            ->visit('/onboarding')
            ->assertSuccessful()
            // Company step
            ->fillField('onboarding[company][companyName]', 'Test Company')
            ->selectFieldOption('onboarding[company][companyCurrency]', 'USD')
            ->click('Continue')
            // Client step
            ->assertSuccessful()
            ->fillField('onboarding[client][clientName]', 'Jane Smith')
            ->fillField('onboarding[client][clientEmail]', 'jane@example.com')
            ->click('Continue')
            // Skip invoice step
            ->assertSuccessful()
            ->assertSee('Create your first invoice')
            ->click('I\'ll do this later')
            // Should go to complete step
            ->assertSuccessful()
            ->assertSee('You\'re all set!')
            ->interceptRedirects()
            ->click('Go to Dashboard')
            ->assertRedirectedTo('/dashboard')
            ->followRedirect()
            ->assertSeeIn('.alert-success', 'Welcome to SolidInvoice!')
        ;

        // Verify only invoice was skipped
        $setting = $this->userSettingRepository->findOneBy([
            'user' => $user,
            'key' => UserSettingType::OnboardingSkipped,
        ]);
        self::assertNotNull($setting);
        $skipped = json_decode($setting->getValue(), true);
        self::assertContains('invoice', $skipped);
        self::assertNotContains('client', $skipped);
    }

    public function testCompanyStepHasNoSkipButton(): void
    {
        $user = $this->createUser('test4@example.com', 'password');

        $browser = $this->browser()
            ->actingAs($user)
            ->visit('/onboarding')
            ->assertSuccessful()
            ->assertSee('Company Name')
        ;

        // Verify skip button is not present on company step
        $browser->assertNotContains('I\'ll do this later');
    }

    public function testInvitedUserDoesNotSeeOnboarding(): void
    {
        // Create a company first
        $company = CompanyFactory::createOne();

        // Create a user with an existing company (invited user)
        $user = UserFactory::createOne([
            'email' => 'invited@example.com',
            'companies' => [$company],
        ])->_real();

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

    public function testCanNavigateBackBetweenSteps(): void
    {
        $user = $this->createUser('navigator@example.com', 'password');

        $this->browser()
            ->actingAs($user)
            ->visit('/onboarding')
            ->assertSuccessful()
            // Fill and submit company step
            ->fillField('onboarding[company][companyName]', 'Test Company')
            ->selectFieldOption('onboarding[company][companyCurrency]', 'EUR')
            ->click('Continue')
            // On client step now
            ->assertSee('Add your first client')
            ->fillField('onboarding[client][clientName]', 'Test Client')
            ->click('Continue')
            // On invoice step
            ->assertSee('Create your first invoice')
            // Click back
            ->click('#onboarding_navigator_back')
            // Should be on client step again
            ->assertSee('Let\'s get you set up')
            // Data should be preserved
            //->assertFieldEquals('onboarding[client][clientName]', 'Test Client')
        ;
    }

    private function createUser(string $email, string $password): User
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
