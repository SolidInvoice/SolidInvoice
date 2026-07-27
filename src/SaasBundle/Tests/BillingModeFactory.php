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

namespace SolidInvoice\SaasBundle\Tests;

use SolidInvoice\SaasBundle\Service\BillingMode;
use SolidWorx\Toggler\ToggleInterface;

/**
 * Builds a real BillingMode over a fake toggle, so tests exercise the same
 * code path production does instead of mocking BillingMode itself.
 */
final class BillingModeFactory
{
    public static function freeTrial(string $couponCode = '', int $couponPercent = 30): BillingMode
    {
        return self::create(false, $couponCode, $couponPercent);
    }

    public static function paidTrial(string $couponCode = '', int $couponPercent = 30): BillingMode
    {
        return self::create(true, $couponCode, $couponPercent);
    }

    private static function create(bool $paidTrial, string $couponCode, int $couponPercent): BillingMode
    {
        $toggle = new readonly class($paidTrial) implements ToggleInterface {
            public function __construct(
                private bool $paidTrial,
            ) {
            }

            public function isActive(string $feature, array $context = []): bool
            {
                return $feature === 'saas_paid_trial' && $this->paidTrial;
            }
        };

        return new BillingMode($toggle, $couponCode, $couponPercent);
    }
}
