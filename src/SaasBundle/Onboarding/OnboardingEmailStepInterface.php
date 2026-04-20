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

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag(OnboardingEmailStepInterface::DI_TAG)]
interface OnboardingEmailStepInterface
{
    public const string DI_TAG = 'solidinvoice.onboarding_email_step';

    /**
     * Stable unique key identifying this step. Persisted in user_settings to
     * track progress across scheduler runs, so it MUST NOT change once released.
     */
    public static function key(): string;

    /**
     * Higher priority runs earlier in the sequence. Ties are broken by key().
     */
    public static function priority(): int;

    /**
     * Return false to skip this email (e.g. the user already completed the action
     * this email would prompt them to do). The scheduler still advances past the
     * step — it will not be retried.
     */
    public function shouldSend(OnboardingContext $context): bool;

    /**
     * Build the TemplatedEmail for this step. The handler sets the envelope
     * from/to after this returns, so implementations only need to set the
     * subject, template, and context.
     */
    public function createEmail(OnboardingContext $context): TemplatedEmail;
}
