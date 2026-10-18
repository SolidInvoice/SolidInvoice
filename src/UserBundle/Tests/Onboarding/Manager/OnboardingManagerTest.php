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

use PHPUnit\Framework\Attributes\CoversClass;
use SolidInvoice\CoreBundle\Repository\CompanyRepository;
use SolidInvoice\CoreBundle\Test\Traits\DoctrineTestTrait;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Enum\UserSettingType;
use SolidInvoice\UserBundle\Onboarding\DTO\OnboardingData;
use SolidInvoice\UserBundle\Onboarding\Manager\OnboardingManager;
use SolidInvoice\UserBundle\Repository\UserSettingRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[CoversClass(OnboardingManager::class)]
final class OnboardingManagerTest extends KernelTestCase
{
    use DoctrineTestTrait;
    use EnsureApplicationInstalled;

    private OnboardingManager $manager;

    private UserSettingRepository $userSettingRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userSettingRepository = self::getContainer()->get(UserSettingRepository::class);

        $this->manager = new OnboardingManager(
            $this->em,
            self::getContainer()->get(CompanyRepository::class),
            $this->userSettingRepository,
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

    public function testCompleteOnboardingCreatesTheCompanyAndMarksOnboardingComplete(): void
    {
        $user = $this->createUser('test8@example.com');
        $this->em->persist($user);
        $this->em->flush();

        $data = new OnboardingData();
        $data->companyName = 'Test Company';
        $data->companyCurrency = 'EUR';

        $company = $this->manager->completeOnboarding($user, $data);

        self::assertSame('Test Company', $company->getName());
        self::assertSame('EUR', $company->currency);
        self::assertSame([$company], $user->getCompanies()->toArray());
        self::assertTrue($this->manager->isOnboardingComplete($user));
        // AffiliateProgram times its prompt from this setting.
        self::assertNotNull($this->userSettingRepository->findOneBy([
            'user' => $user,
            'key' => UserSettingType::OnboardingCompletedAt,
        ]));
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
