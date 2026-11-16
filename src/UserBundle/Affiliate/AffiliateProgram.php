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

namespace SolidInvoice\UserBundle\Affiliate;

use DateMalformedStringException;
use DateTimeImmutable;
use DateTimeInterface;
use Psr\Clock\ClockInterface;
use SolidInvoice\DashboardBundle\Checklist\ChecklistManager;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Entity\UserSetting;
use SolidInvoice\UserBundle\Enum\UserSettingType;
use SolidInvoice\UserBundle\Repository\UserSettingRepositoryInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Attribute\AsTwigFunction;

/**
 * The SolidInvoice Cloud affiliate programme: its configuration, and the one place that decides
 * whether a user gets the one-off dashboard prompt to join it.
 *
 * @see \SolidInvoice\UserBundle\Tests\Affiliate\AffiliateProgramTest
 */
final readonly class AffiliateProgram
{
    public function __construct(
        private UserSettingRepositoryInterface $userSettingRepository,
        private ChecklistManager $checklistManager,
        private ClockInterface $clock,
        #[Autowire(env: 'bool:SOLIDINVOICE_AFFILIATE_PROGRAM_ENABLED')]
        private bool $enabled,
        #[Autowire(env: 'SOLIDINVOICE_AFFILIATE_SIGNUP_URL')]
        private string $signupUrl,
        #[Autowire(env: 'SOLIDINVOICE_AFFILIATE_TERMS_URL')]
        private string $termsUrl,
        #[Autowire(env: 'SOLIDINVOICE_AFFILIATE_COMMISSION')]
        private string $commission,
        #[Autowire(env: 'SOLIDINVOICE_AFFILIATE_YEARLY_EARNINGS')]
        private string $yearlyEarnings,
        #[Autowire(env: 'int:SOLIDINVOICE_AFFILIATE_PROMPT_DELAY_DAYS')]
        private int $promptDelayDays,
    ) {
    }

    #[AsTwigFunction(name: 'affiliate_program')]
    public function forTwig(): self
    {
        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function getSignupUrl(): string
    {
        return $this->signupUrl;
    }

    public function getTermsUrl(): string
    {
        return $this->termsUrl;
    }

    public function getCommission(): string
    {
        return $this->commission;
    }

    /**
     * The most one referred customer can earn the affiliate in a year, for display. A dollar figure
     * reads bigger than a percentage of a sub-$100 plan.
     */
    public function getYearlyEarnings(): string
    {
        return $this->yearlyEarnings;
    }

    /**
     * Cheapest checks first: the checklist one runs every checklist item's completion query.
     */
    public function shouldShowPrompt(User $user): bool
    {
        if (! $this->enabled) {
            return false;
        }

        $completedAt = $this->userSettingRepository->getSetting($user, UserSettingType::OnboardingCompletedAt)?->getValue();

        if ($completedAt === null || $completedAt === '') {
            return false;
        }

        try {
            $eligibleFrom = new DateTimeImmutable($completedAt)->modify(sprintf('+%d days', $this->promptDelayDays));
        } catch (DateMalformedStringException) {
            return false;
        }

        if ($eligibleFrom > $this->clock->now()) {
            return false;
        }

        if ($this->userSettingRepository->getSetting($user, UserSettingType::AffiliatePromptDismissedAt) instanceof UserSetting) {
            return false;
        }

        return ! $this->isChecklistActive($user);
    }

    public function dismissPrompt(User $user): void
    {
        $this->userSettingRepository->saveSetting(
            $user,
            UserSettingType::AffiliatePromptDismissedAt,
            $this->clock->now()->format(DateTimeInterface::ATOM),
        );
    }

    /**
     * Mirrors what the onboarding checklist widget renders: a finished checklist only shows its
     * success state, so it no longer counts as onboarding in progress.
     */
    private function isChecklistActive(User $user): bool
    {
        if ($this->checklistManager->isDismissed($user)) {
            return false;
        }

        $progress = $this->checklistManager->getProgress();

        return $progress->items !== [] && ! $progress->allComplete;
    }
}
