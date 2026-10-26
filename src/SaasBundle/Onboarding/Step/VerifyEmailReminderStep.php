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
use SolidInvoice\SaasBundle\Onboarding\OnboardingContext;
use SolidWorx\Platform\PlatformBundle\Security\EmailVerification\EmailVerificationGateInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * An unverified user cannot send an invoice, so a day in, and again at three
 * days, they are reminded to confirm their email (an account first reached after
 * three days gets only the final reminder). The handler adds the verify
 * link, and lets this step (and the final one) through to an unverified address.
 */
#[AsTaggedItem(priority: 90)]
class VerifyEmailReminderStep extends AbstractOnboardingEmailStep
{
    public function __construct(
        TranslatorInterface $translator,
        private readonly EmailVerificationGateInterface $emailVerificationGate,
    ) {
        parent::__construct($translator);
    }

    public static function key(): string
    {
        return 'verify_email_reminder';
    }

    public static function priority(): int
    {
        return 90;
    }

    #[Override]
    public function shouldSend(OnboardingContext $context): bool
    {
        $hours = $context->hoursSinceTrialStart();
        $before = static::beforeHours();

        return $hours >= static::afterHours()
            && ($before === null || $hours < $before)
            && $this->emailVerificationGate->isGated($context->user);
    }

    #[Override]
    public function createEmail(OnboardingContext $context): TemplatedEmail
    {
        // Both reminders share one template; only the copy keys differ.
        return parent::createEmail($context)
            ->htmlTemplate('@SolidInvoiceSaas/Email/Onboarding/verify_email_reminder.html.twig')
            ->textTemplate('@SolidInvoiceSaas/Email/Onboarding/verify_email_reminder.txt.twig');
    }

    #[Override]
    protected function templateContext(OnboardingContext $context): array
    {
        return parent::templateContext($context) + ['copy' => 'onboarding.' . static::key()];
    }

    protected static function afterHours(): int
    {
        return 24;
    }

    /**
     * Once the final reminder is due, the first one no longer is. Otherwise an
     * account that reaches the sequence late (an audience change, a backlog)
     * gets both, one tick (an hour) apart.
     */
    protected static function beforeHours(): ?int
    {
        return VerifyEmailFinalReminderStep::afterHours();
    }
}
