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

namespace SolidInvoice\SaasBundle\Onboarding\Step;

use Override;
use SolidInvoice\SaasBundle\Onboarding\OnboardingAudience;
use SolidInvoice\SaasBundle\Onboarding\OnboardingContext;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * The feedback email again, a month into Free. Moving to Free restarts the
 * subscription, so its start date is the day it moved (or signed up).
 */
#[AsTaggedItem(priority: 10)]
final class FreeFeedbackStep extends AbstractOnboardingEmailStep
{
    public const int AFTER_DAYS = 30;

    public static function key(): string
    {
        return 'free_feedback';
    }

    public static function priority(): int
    {
        return 10;
    }

    #[Override]
    public static function audience(): OnboardingAudience
    {
        return OnboardingAudience::Free;
    }

    #[Override]
    public function shouldSend(OnboardingContext $context): bool
    {
        return $context->hoursSinceTrialStart() >= self::AFTER_DAYS * 24;
    }

    #[Override]
    public function createEmail(OnboardingContext $context): TemplatedEmail
    {
        return parent::createEmail($context)
            ->subject($this->translator->trans('onboarding.feedback.subject', [], 'email'))
            ->htmlTemplate('@SolidInvoiceSaas/Email/Onboarding/feedback.html.twig')
            ->textTemplate('@SolidInvoiceSaas/Email/Onboarding/feedback.txt.twig');
    }
}
