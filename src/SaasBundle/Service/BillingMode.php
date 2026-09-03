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

use SolidWorx\Toggler\ToggleInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Single point of interpretation for how this deployment sells subscriptions.
 *
 * Keeping the toggle key in one place means callers depend on the intent
 * ("does a trial require a card?") rather than on the toggle name, and gives
 * tests a single seam to drive.
 *
 * @see \SolidInvoice\SaasBundle\Tests\Service\BillingModeTest
 */
final readonly class BillingMode
{
    public function __construct(
        private ToggleInterface $toggle,
        #[Autowire(env: 'SOLIDINVOICE_SAAS_ONBOARDING_COUPON_CODE')]
        private string $couponCode = '',
        #[Autowire(env: 'int:SOLIDINVOICE_SAAS_ONBOARDING_COUPON_PERCENT')]
        private int $couponPercent = 30,
    ) {
    }

    /**
     * True when the trial only starts after the user enters payment details at
     * the payment provider. The provider owns the trial window; a variant with
     * no trial configured simply charges on the first payment.
     */
    public function requiresCardForTrial(): bool
    {
        return $this->toggle->isActive('saas_paid_trial');
    }

    /**
     * The onboarding coupon, or an empty string when it should not be offered.
     *
     * A paid trial already has a card on file and a discount applied at
     * checkout, so there is nothing left for the user to redeem — suppressing
     * it centrally keeps every consumer (banner, expired page, emails) honest.
     */
    public function couponCode(): string
    {
        return $this->requiresCardForTrial() ? '' : $this->couponCode;
    }

    public function couponPercent(): int
    {
        return $this->couponPercent;
    }
}
