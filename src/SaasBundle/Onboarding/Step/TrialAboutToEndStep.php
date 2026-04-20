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

use Psr\Clock\ClockInterface;
use SolidInvoice\SaasBundle\Onboarding\OnboardingContext;
use Symfony\Contracts\Translation\TranslatorInterface;

final class TrialAboutToEndStep extends AbstractOnboardingEmailStep
{
    public function __construct(
        TranslatorInterface $translator,
        private readonly ClockInterface $clock,
    ) {
        parent::__construct($translator);
    }

    public static function key(): string
    {
        return 'trial_about_to_end';
    }

    public static function priority(): int
    {
        return 50;
    }

    protected function templateContext(OnboardingContext $context): array
    {
        $now = $this->clock->now();

        $daysRemaining = $now < $context->trialEnd
            ? $now->diff($context->trialEnd)->days
            : 0;

        return parent::templateContext($context) + [
            'days_remaining' => $daysRemaining,
        ];
    }
}
