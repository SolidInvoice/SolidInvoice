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

namespace SolidInvoice\SaasBundle\Service;

use Psr\Clock\ClockInterface;
use SolidInvoice\SaasBundle\Plan\TrialPeriod;
use SolidWorx\Platform\SaasBundle\Entity\Subscription;
use SolidWorx\Platform\SaasBundle\Enum\SubscriptionStatus;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use function ceil;

/**
 * Decides whether the injected top-of-page banner should show for the current
 * subscription and, for trials, which variant. Pure: no translator, no Twig,
 * no URL generation — returns translation keys/params for the listener to render.
 *
 * @see \SolidInvoice\SaasBundle\Tests\Service\TrialBannerResolverTest
 */
final readonly class TrialBannerResolver
{
    public function __construct(
        private ClockInterface $clock,
        private BillingMode $billingMode,
        private TrialPeriod $trialPeriod,
        #[Autowire(env: 'int:SOLIDINVOICE_SAAS_TRIAL_BANNER_DAYS')]
        private int $bannerDays = 7,
        #[Autowire(env: 'int:SOLIDINVOICE_SAAS_TRIAL_COUPON_DAYS')]
        private int $couponDays = 2,
    ) {
    }

    public function resolve(Subscription $subscription): ?TrialBanner
    {
        $now = $this->clock->now();

        if ($subscription->getStatus() === SubscriptionStatus::CANCELLED) {
            if ($subscription->getEndDate() <= $now) {
                return null;
            }

            return new TrialBanner(
                'danger',
                'tabler:alert-circle',
                'saas.trial_banner.cancelled.title',
                'saas.trial_banner.cancelled.message',
                ['%date%' => $subscription->getEndDate()->format('F j, Y')],
                'saas.trial_banner.cancelled.cta',
            );
        }

        if ($subscription->getStatus() !== SubscriptionStatus::TRIAL) {
            return null;
        }

        // Below here the banner is a trial *incentive* — extend the trial, or
        // redeem a coupon. A paid trial already has a card on file, so neither
        // is on offer. The cancelled notice above still applies in both modes.
        if ($this->billingMode->requiresCardForTrial()) {
            return null;
        }

        // A card is already on file (Lemon Squeezy is billing this trial); the
        // extension has already happened, so nudging them again is wrong.
        if ($subscription->isExternallyBilled()) {
            return null;
        }

        $endDate = $subscription->getEndDate();

        // Expired trials are handled by the full-page block in onRequest.
        if ($endDate <= $now) {
            return null;
        }

        $daysRemaining = (int) ceil(($endDate->getTimestamp() - $now->getTimestamp()) / 86400);

        if ($daysRemaining > $this->bannerDays) {
            return null;
        }

        // The trial length is synced onto the Plan from the payment provider, so
        // the "get N more days" copy always reflects the real trial the user
        // receives by adding a card. Without a trial length we cannot make that
        // promise, so no banner is shown.
        $extensionDays = $this->trialPeriod->days($subscription->getPlan());

        if ($extensionDays === null) {
            return null;
        }

        $couponCode = $this->billingMode->couponCode();

        if ($couponCode !== '' && $daysRemaining <= $this->couponDays) {
            return new TrialBanner(
                'info',
                'tabler:gift',
                'saas.trial_banner.coupon.title',
                'saas.trial_banner.coupon.message',
                [
                    '%days%' => $extensionDays,
                    '%percent%' => $this->billingMode->couponPercent(),
                    '%code%' => $couponCode,
                ],
                'saas.trial_banner.coupon.cta',
                $couponCode,
            );
        }

        return new TrialBanner(
            'info',
            'tabler:calendar-plus',
            'saas.trial_banner.extend.title',
            'saas.trial_banner.extend.message',
            ['%days%' => $extensionDays],
            'saas.trial_banner.extend.cta',
        );
    }
}
