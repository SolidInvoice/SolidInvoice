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

use SolidInvoice\SaasBundle\Onboarding\OnboardingContext;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Translation\TranslatorInterface;

final class UpgradeOfferStep extends AbstractOnboardingEmailStep
{
    public function __construct(
        TranslatorInterface $translator,
        #[Autowire(env: 'SOLIDINVOICE_SAAS_ONBOARDING_COUPON_CODE')]
        private readonly string $couponCode = '',
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

    protected function templateContext(OnboardingContext $context): array
    {
        return parent::templateContext($context) + [
            'coupon_code' => $this->couponCode,
        ];
    }
}
