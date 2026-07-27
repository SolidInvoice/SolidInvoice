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

namespace SolidInvoice\SaasBundle\Twig;

use Override;
use SolidInvoice\SaasBundle\Plan\TrialPeriod;
use SolidWorx\Platform\SaasBundle\Entity\Plan;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Exposes a plan's trial length to templates so plan-picker copy can promise
 * the real number of days. Whether a card is required up front is already
 * available in Twig as `toggle('saas_paid_trial')`.
 */
final class SubscriptionExtension extends AbstractExtension
{
    public function __construct(
        private readonly TrialPeriod $trialPeriod,
    ) {
    }

    /**
     * @return list<TwigFunction>
     */
    #[Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('saas_plan_trial_days', $this->planTrialDays(...)),
        ];
    }

    public function planTrialDays(Plan $plan): ?int
    {
        return $this->trialPeriod->days($plan);
    }
}
