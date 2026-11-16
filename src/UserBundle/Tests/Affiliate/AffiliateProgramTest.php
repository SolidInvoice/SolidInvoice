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

namespace SolidInvoice\UserBundle\Tests\Affiliate;

use DateTimeImmutable;
use DateTimeInterface;
use SolidInvoice\DashboardBundle\Checklist\ChecklistManager;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\UserBundle\Affiliate\AffiliateProgram;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Enum\UserSettingType;
use SolidInvoice\UserBundle\Repository\UserSettingRepositoryInterface;
use SolidInvoice\UserBundle\Test\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\MockClock;

final class AffiliateProgramTest extends KernelTestCase
{
    use EnsureApplicationInstalled;

    private const string NOW = '2026-10-15 12:00:00';

    private UserSettingRepositoryInterface $settings;

    protected function setUp(): void
    {
        $this->settings = self::getContainer()->get(UserSettingRepositoryInterface::class);
    }

    public function testEligibleOnceTheDelayHasPassed(): void
    {
        $user = $this->eligibleUser();

        self::assertTrue($this->program()->shouldShowPrompt($user));
    }

    public function testNotShownWhenTheProgramIsDisabled(): void
    {
        $user = $this->eligibleUser();

        self::assertFalse($this->program(enabled: false)->shouldShowPrompt($user));
    }

    public function testNotShownBeforeOnboardingIsComplete(): void
    {
        $user = $this->eligibleUser();
        $this->settings->removeSetting($user, UserSettingType::OnboardingCompletedAt);

        self::assertFalse($this->program()->shouldShowPrompt($user));
    }

    public function testNotShownBeforeTheDelayHasPassed(): void
    {
        $user = $this->eligibleUser(onboardedAt: '2026-10-12 12:00:01');

        self::assertFalse($this->program()->shouldShowPrompt($user));
    }

    public function testNotShownOnceDismissed(): void
    {
        $user = $this->eligibleUser();
        $this->settings->saveSetting($user, UserSettingType::AffiliatePromptDismissedAt, '2026-10-14T09:00:00+00:00');

        self::assertFalse($this->program()->shouldShowPrompt($user));
    }

    public function testNotShownWhileTheOnboardingChecklistIsInProgress(): void
    {
        $user = $this->eligibleUser();
        $this->settings->removeSetting($user, UserSettingType::OnboardingChecklistDismissed);

        // A fresh company has no clients or invoices, so the checklist is still open.
        self::assertFalse(self::getContainer()->get(ChecklistManager::class)->getProgress()->allComplete);
        self::assertFalse($this->program()->shouldShowPrompt($user));
    }

    public function testDismissingStoresWhenItHappened(): void
    {
        $user = $this->eligibleUser();

        $this->program()->dismissPrompt($user);

        self::assertSame(
            new DateTimeImmutable(self::NOW)->format(DateTimeInterface::ATOM),
            $this->settings->getSetting($user, UserSettingType::AffiliatePromptDismissedAt)?->getValue(),
        );
        self::assertFalse($this->program()->shouldShowPrompt($user));
    }

    private function eligibleUser(string $onboardedAt = '2026-10-12 12:00:00'): User
    {
        $user = UserFactory::createOne(['companies' => [$this->company]]);

        $this->settings->saveSetting($user, UserSettingType::OnboardingCompletedAt, $onboardedAt);
        $this->settings->saveSetting($user, UserSettingType::OnboardingChecklistDismissed, 'true');

        return $user;
    }

    private function program(bool $enabled = true): AffiliateProgram
    {
        return new AffiliateProgram(
            $this->settings,
            self::getContainer()->get(ChecklistManager::class),
            new MockClock(new DateTimeImmutable(self::NOW)),
            $enabled,
            'https://billing.example.com/affiliates',
            '',
            '20%',
            '$93',
            3,
        );
    }
}
