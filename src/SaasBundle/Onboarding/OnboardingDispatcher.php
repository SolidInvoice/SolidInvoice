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

use LogicException;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use SolidInvoice\SaasBundle\Message\SendOnboardingEmailMessage;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Enum\UserSettingType;
use SolidInvoice\UserBundle\Repository\UserSettingRepositoryInterface;
use SolidWorx\Platform\SaasBundle\Entity\Subscription;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Per-user tick driver for the onboarding email sequence.
 *
 * Called from the hourly scheduler command. Looks up the user's progress in
 * user_settings, computes the next due step using the schedule calculator,
 * and dispatches a Messenger message for the handler to actually send.
 *
 * The progress setting is advanced BEFORE the message is dispatched so that
 * a scheduler tick cannot produce duplicate dispatches even if Messenger
 * retries internally. Messenger's 3-retry exponential backoff covers
 * transient failures; truly permanent failures move to the failed queue and
 * the sequence moves on.
 */
final readonly class OnboardingDispatcher
{
    public function __construct(
        private OnboardingStepRegistry $registry,
        private OnboardingScheduleCalculator $scheduleCalculator,
        private UserSettingRepositoryInterface $userSettingRepository,
        private MessageBusInterface $messageBus,
        private ClockInterface $clock,
        private LoggerInterface $logger,
    ) {
    }

    public function tick(User $user, Subscription $subscription): void
    {
        $lastSetting = $this->userSettingRepository->getSetting(
            $user,
            UserSettingType::OnboardingEmailSequenceLastStep,
        );
        $lastKey = $lastSetting?->getValue();

        $next = $this->registry->nextAfter($lastKey);

        if ($next === null) {
            return;
        }

        $stepCount = $this->registry->count();
        $index = $this->registry->indexOf($next::key());

        if ($index === null) {
            return;
        }

        $target = $this->scheduleCalculator->targetTimeFor($subscription, $index, $stepCount);

        if ($this->clock->now() < $target) {
            return;
        }

        $userId = $user->getId() ?? throw new LogicException('User must be persisted before dispatching onboarding email.');

        $this->userSettingRepository->saveSetting(
            $user,
            UserSettingType::OnboardingEmailSequenceLastStep,
            $next::key(),
        );

        $this->messageBus->dispatch(new SendOnboardingEmailMessage($userId, $next::key()));

        $this->logger->info('Dispatched onboarding email step', [
            'user_id' => $userId->toString(),
            'step_key' => $next::key(),
            'step_index' => $index,
        ]);
    }
}
