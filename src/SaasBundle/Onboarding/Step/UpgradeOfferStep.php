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
use SolidInvoice\SaasBundle\Service\BillingMode;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsTaggedItem(priority: 40)]
final class UpgradeOfferStep extends AbstractOnboardingEmailStep
{
    public function __construct(
        TranslatorInterface $translator,
        private readonly BillingMode $billingMode,
    ) {
        parent::__construct($translator);
    }

    /**
     * The upgrade offer exists to convert a free trial before it lapses. A paid
     * trial converts on its own when the provider bills the card on file, and
     * the coupon it advertises cannot be redeemed on an existing subscription.
     */
    #[Override]
    public function shouldSend(OnboardingContext $context): bool
    {
        return ! $this->billingMode->requiresCardForTrial() && parent::shouldSend($context);
    }

    public static function key(): string
    {
        return 'upgrade_offer';
    }

    public static function priority(): int
    {
        return 40;
    }

    #[Override]
    protected function templateContext(OnboardingContext $context): array
    {
        return parent::templateContext($context) + [
            'coupon_code' => $this->billingMode->couponCode(),
            'coupon_percent' => $this->billingMode->couponPercent(),
        ];
    }
}
