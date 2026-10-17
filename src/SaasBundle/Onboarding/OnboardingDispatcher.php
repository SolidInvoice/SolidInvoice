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

namespace SolidInvoice\SaasBundle\Onboarding;

use DateTimeImmutable;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use SolidInvoice\SaasBundle\Message\SendOnboardingEmailMessage;
use SolidInvoice\SaasBundle\Onboarding\Step\FreeFeedbackStep;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Enum\UserSettingType;
use SolidInvoice\UserBundle\Repository\UserSettingRepositoryInterface;
use SolidWorx\Platform\SaasBundle\Entity\Subscription;
use SolidWorx\Platform\SaasBundle\Enum\SubscriptionStatus;
use Symfony\Component\Messenger\MessageBusInterface;
use function array_filter;
use function array_search;
use function array_slice;
use function array_values;
use function explode;
use function implode;
use function in_array;

/**
 * Per-user tick driver for the onboarding email sequence.
 *
 * Called from the hourly scheduler command, and once at signup so the welcome
 * goes out straight away. Each tick asks every step not yet sent whether it is
 * due (by elapsed time, by what the user has done, or both) and dispatches the
 * first one that is. One email per tick keeps two that come due together from
 * landing in the same minute.
 *
 * The sent list is saved BEFORE the message is dispatched so that a tick cannot
 * produce duplicate dispatches even if Messenger retries internally. Messenger's
 * 3-retry exponential backoff covers transient failures; truly permanent
 * failures move to the failed queue and the step still counts as sent.
 *
 * @see \SolidInvoice\SaasBundle\Tests\Onboarding\OnboardingDispatcherTest
 */
final readonly class OnboardingDispatcher
{
    /**
     * The evenly spaced sequence that preceded the behaviour-triggered one. Users
     * part-way through it only stored the last key they reached.
     *
     * ponytail: drop this, and UserSettingType::OnboardingEmailSequenceLastStep,
     * once every trial that started before the change has ended.
     */
    private const array LEGACY_SEQUENCE = [
        'welcome',
        'add_first_client',
        'send_first_invoice',
        'turn_invoices_into_payments',
        'recurring_billing',
        'customize_logo',
        'feedback',
        'trial_about_to_end',
        'upgrade_offer',
    ];

    public function __construct(
        private OnboardingStepRegistry $registry,
        private UserSettingRepositoryInterface $userSettingRepository,
        private MessageBusInterface $messageBus,
        private ClockInterface $clock,
        private LoggerInterface $logger,
    ) {
    }

    public function tick(User $user, Subscription $subscription): void
    {
        $context = OnboardingContext::fromSubscription($user, $subscription, DateTimeImmutable::createFromInterface($this->clock->now()));

        if (! $context instanceof OnboardingContext) {
            return;
        }

        $sent = $this->sentKeys($user);

        // The trial steps don't look at the status, so an account already past its
        // trial (on Free) is only offered the step meant for it.
        $pastTrial = $subscription->getStatus() === SubscriptionStatus::ACTIVE;

        foreach ($this->registry->all() as $step) {
            if (($pastTrial && ! $step instanceof FreeFeedbackStep) || in_array($step::key(), $sent, true) || ! $step->shouldSend($context)) {
                continue;
            }

            $sent[] = $step::key();

            $this->userSettingRepository->saveSetting($user, UserSettingType::OnboardingEmailsSent, implode(',', $sent));

            $this->messageBus->dispatch(new SendOnboardingEmailMessage($user->getId(), $step::key()));

            $this->logger->info('Dispatched onboarding email step', [
                'user_id' => $user->getId()->toString(),
                'step_key' => $step::key(),
            ]);

            return;
        }
    }

    /**
     * @return list<string>
     */
    private function sentKeys(User $user): array
    {
        $sent = $this->userSettingRepository->getSetting($user, UserSettingType::OnboardingEmailsSent)?->getValue();

        if ($sent !== null) {
            return array_values(array_filter(explode(',', $sent)));
        }

        $legacyLast = $this->userSettingRepository->getSetting($user, UserSettingType::OnboardingEmailSequenceLastStep)?->getValue();
        $legacyIndex = array_search($legacyLast, self::LEGACY_SEQUENCE, true);

        return $legacyIndex === false ? [] : array_slice(self::LEGACY_SEQUENCE, 0, $legacyIndex + 1);
    }
}
