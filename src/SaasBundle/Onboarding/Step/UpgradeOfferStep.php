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
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The discount code on its own, two days before the trial ends, with a subject
 * that says it is a gift. The trial-ending email carries the code too; this one
 * is the nudge for whoever skimmed past it.
 */
#[AsTaggedItem(priority: 40)]
final class UpgradeOfferStep extends AbstractOnboardingEmailStep
{
    private const int NOTICE_DAYS = 2;

    public function __construct(
        TranslatorInterface $translator,
        private readonly BillingMode $billingMode,
    ) {
        parent::__construct($translator);
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
    public function shouldSend(OnboardingContext $context): bool
    {
        // No code is configured in card mode, and a code cannot be redeemed on a
        // subscription that is already billed.
        return '' !== $this->billingMode->couponCode()
            && ! $context->subscription->isExternallyBilled()
            && $context->now >= $context->trialEnd->modify('-' . self::NOTICE_DAYS . ' days');
    }

    #[Override]
    public function createEmail(OnboardingContext $context): TemplatedEmail
    {
        return parent::createEmail($context)->subject($this->translator->trans('onboarding.upgrade_offer.subject', [
            '%percent%' => $this->billingMode->couponPercent(),
            '%plan%' => $context->plan->getName(),
        ], 'email'));
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
