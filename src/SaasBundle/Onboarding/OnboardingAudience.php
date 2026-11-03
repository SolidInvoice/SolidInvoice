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
use DateTimeInterface;
use SolidInvoice\SaasBundle\Onboarding\Step\FreeFeedbackStep;
use SolidInvoice\UserBundle\Entity\User;
use SolidWorx\Platform\SaasBundle\Entity\Subscription;
use SolidWorx\Platform\SaasBundle\Enum\SubscriptionStatus;

/**
 * Which accounts an onboarding step is written for. Paying customers are in no
 * audience: the sequence is for accounts still deciding.
 *
 * Onboarding is for new users. Free and Any steps only go to accounts created
 * in the last NEW_USER_DAYS, so a long-standing customer who moves to Free, adds
 * a company or is invited to one is never welcomed again. Trial steps follow the
 * trial itself, whenever it is taken.
 *
 * Subscription.status is not flipped when a trial lapses (only the end date
 * says so), so a trial counts as running until its end date.
 */
enum OnboardingAudience
{
    case Free;
    case Trial;
    case Any;

    // Long enough for the Free check-in, which comes a month into Free after
    // a trial of up to two weeks.
    public const int NEW_USER_DAYS = 2 * FreeFeedbackStep::AFTER_DAYS;

    public function matches(Subscription $subscription, User $user, DateTimeInterface $now): bool
    {
        $onTrial = $subscription->getStatus() === SubscriptionStatus::TRIAL && $subscription->getEndDate() > $now;
        $onFree = $subscription->getStatus() === SubscriptionStatus::ACTIVE && $subscription->getPlan()->isFree();
        $created = $user->getCreated();
        $newUser = $created instanceof DateTimeInterface && $created > DateTimeImmutable::createFromInterface($now)->modify('-' . self::NEW_USER_DAYS . ' days');

        return match ($this) {
            self::Trial => $onTrial,
            self::Free => $onFree && $newUser,
            self::Any => ($onFree || $onTrial) && $newUser,
        };
    }
}
