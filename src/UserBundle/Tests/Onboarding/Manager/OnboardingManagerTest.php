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

namespace SolidInvoice\UserBundle\Tests\Onboarding\Manager;

use SolidInvoice\ClientBundle\Repository\ClientRepository;
use SolidInvoice\CoreBundle\Repository\CompanyRepository;
use SolidInvoice\CoreBundle\Test\Traits\DoctrineTestTrait;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Repository\InvoiceRepository;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Enum\UserSettingType;
use SolidInvoice\UserBundle\Onboarding\DTO\OnboardingData;
use SolidInvoice\UserBundle\Onboarding\Manager\OnboardingManager;
use SolidInvoice\UserBundle\Repository\UserSettingRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** @covers \SolidInvoice\UserBundle\Onboarding\Manager\OnboardingManager */
final class OnboardingManagerTest extends KernelTestCase
{
    use DoctrineTestTrait;
    use EnsureApplicationInstalled;

    private OnboardingManager $manager;

    private UserSettingRepository $userSettingRepository;

    private CompanyRepository $companyRepository;

    private ClientRepository $clientRepository;

    private InvoiceRepository $invoiceRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userSettingRepository = self::getContainer()->get(UserSettingRepository::class);
        $this->companyRepository = self::getContainer()->get(CompanyRepository::class);
        $this->clientRepository = self::getContainer()->get(ClientRepository::class);
        $this->invoiceRepository = self::getContainer()->get(InvoiceRepository::class);

        // Manually create OnboardingManager since it may not be public in test container
        $this->manager = new OnboardingManager(
            $this->em,
            $this->companyRepository,
            $this->clientRepository,
            $this->invoiceRepository,
            $this->userSettingRepository
        );
    }

    public function testIsOnboardingCompleteReturnsFalseWhenNotComplete(): void
    {
        $user = $this->createUser('test@example.com');
        $this->em->persist($user);
        $this->em->flush();

        self::assertFalse($this->manager->isOnboardingComplete($user));
    }

    public function testIsOnboardingCompleteReturnsTrueWhenComplete(): void
    {
        $user = $this->createUser('test2@example.com');
        $this->em->persist($user);
        $this->em->flush();

        $this->manager->dismissOnboarding($user);

        self::assertTrue($this->manager->isOnboardingComplete($user));
    }

    public function testGetCurrentStepReturnsNullWhenNotSet(): void
    {
        $user = $this->createUser('test3@example.com');
        $this->em->persist($user);
        $this->em->flush();

        self::assertNull($this->manager->getCurrentStep($user));
    }

    public function testGetCurrentStepReturnsStepName(): void
    {
        $user = $this->createUser('test4@example.com');
        $this->em->persist($user);
        $this->em->flush();

        $this->manager->setCurrentStep($user, 'client');

        self::assertSame('client', $this->manager->getCurrentStep($user));
    }

    public function testSetCurrentStepSavesSetting(): void
    {
        $user = $this->createUser('test5@example.com');
        $this->em->persist($user);
        $this->em->flush();

        $this->manager->setCurrentStep($user, 'invoice');

        $setting = $this->userSettingRepository->findOneBy([
            'user' => $user,
            'key' => UserSettingType::OnboardingStep,
        ]);

        self::assertNotNull($setting);
        self::assertSame('invoice', $setting->getValue());
    }

    public function testMarkStepSkippedAddsStepToSkippedList(): void
    {
        $user = $this->createUser('test6@example.com');
        $this->em->persist($user);
        $this->em->flush();

        $this->manager->markStepSkipped($user, 'client');

        $setting = $this->userSettingRepository->findOneBy([
            'user' => $user,
            'key' => UserSettingType::OnboardingSkipped,
        ]);

        self::assertNotNull($setting);
        $skipped = json_decode($setting->getValue(), true);
        self::assertSame(['client'], $skipped);
    }

    public function testStartOnboardingSetsInitialStep(): void
    {
        $user = $this->createUser('test7@example.com');
        $this->em->persist($user);
        $this->em->flush();

        $this->manager->startOnboarding($user);

        self::assertSame('company', $this->manager->getCurrentStep($user));

        $startedAtSetting = $this->userSettingRepository->findOneBy([
            'user' => $user,
            'key' => UserSettingType::OnboardingStartedAt,
        ]);
        self::assertNotNull($startedAtSetting);
    }

    public function testCompleteOnboardingWithFullData(): void
    {
        $user = $this->createUser('test8@example.com');
        $this->em->persist($user);
        $this->em->flush();

        $data = new OnboardingData();
        $data->companyName = 'Test Company';
        $data->companyCurrency = 'USD';
        $data->clientName = 'Test Client';
        $data->clientEmail = 'client@example.com';
        $data->invoiceDescription = 'Test Service';
        $data->invoiceAmount = '1000.00';

        $invoice = $this->manager->completeOnboarding($user, $data);

        self::assertInstanceOf(Invoice::class, $invoice);
        self::assertTrue($this->manager->isOnboardingComplete($user));

        // Verify company was created
        self::assertCount(1, $user->getCompanies());
        $company = $user->getCompanies()->first();
        self::assertSame('Test Company', $company->getName());

        // Verify client was created
        $clients = $this->clientRepository->findBy(['company' => $company]);
        self::assertCount(1, $clients);
        self::assertSame('Test Client', $clients[0]->getName());

        // Verify invoice was created
        $invoices = $this->invoiceRepository->findBy(['company' => $company]);
        self::assertCount(1, $invoices);
        self::assertSame('Test Service', $invoices[0]->getLines()->first()->getDescription());
    }

    public function testCompleteOnboardingWithoutClientAndInvoice(): void
    {
        $user = $this->createUser('test9@example.com');
        $this->em->persist($user);
        $this->em->flush();

        $data = new OnboardingData();
        $data->companyName = 'Test Company';
        $data->companyCurrency = 'EUR';

        $invoice = $this->manager->completeOnboarding($user, $data);

        self::assertNull($invoice);
        self::assertTrue($this->manager->isOnboardingComplete($user));

        // Verify company was created
        self::assertCount(1, $user->getCompanies());
        $company = $user->getCompanies()->first();
        self::assertSame('Test Company', $company->getName());
        self::assertSame('EUR', $company->currency);

        // Verify no clients or invoices were created
        self::assertCount(0, $this->clientRepository->findBy(['company' => $company]));
        self::assertCount(0, $this->invoiceRepository->findBy(['company' => $company]));

        // Verify steps were marked as skipped
        $setting = $this->userSettingRepository->findOneBy([
            'user' => $user,
            'key' => UserSettingType::OnboardingSkipped,
        ]);
        self::assertNotNull($setting);
        $skipped = json_decode($setting->getValue(), true);
        self::assertContains('client', $skipped);
        self::assertContains('invoice', $skipped);
    }

    public function testCompleteOnboardingWithClientButNoInvoice(): void
    {
        $user = $this->createUser('test10@example.com');
        $this->em->persist($user);
        $this->em->flush();

        $data = new OnboardingData();
        $data->companyName = 'Test Company';
        $data->companyCurrency = 'GBP';
        $data->clientName = 'Jane Doe';
        $data->clientEmail = 'jane@example.com';

        $invoice = $this->manager->completeOnboarding($user, $data);

        self::assertNull($invoice);
        self::assertTrue($this->manager->isOnboardingComplete($user));

        // Verify company and client were created
        $company = $user->getCompanies()->first();
        self::assertCount(1, $this->clientRepository->findBy(['company' => $company]));

        // Verify no invoice was created
        self::assertCount(0, $this->invoiceRepository->findBy(['company' => $company]));

        // Verify only invoice step was skipped
        $setting = $this->userSettingRepository->findOneBy([
            'user' => $user,
            'key' => UserSettingType::OnboardingSkipped,
        ]);
        self::assertNotNull($setting);
        $skipped = json_decode($setting->getValue(), true);
        self::assertContains('invoice', $skipped);
        self::assertNotContains('client', $skipped);
    }

    public function testDismissOnboardingMarksAsComplete(): void
    {
        $user = $this->createUser('test11@example.com');
        $this->em->persist($user);
        $this->em->flush();

        $this->manager->dismissOnboarding($user);

        $setting = $this->userSettingRepository->findOneBy([
            'user' => $user,
            'key' => UserSettingType::OnboardComplete,
        ]);

        self::assertNotNull($setting);
        self::assertSame('dismissed', $setting->getValue());
        self::assertTrue($this->manager->isOnboardingComplete($user));
    }

    private function createUser(string $email): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setPassword('dummy-password');
        return $user;
    }
}
