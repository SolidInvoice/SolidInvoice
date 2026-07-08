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

namespace SolidInvoice\CoreBundle\Subscription;

use Override;
use SolidInvoice\CoreBundle\Contracts\PaidSubscriptionGateInterface;
use SolidInvoice\CoreBundle\Entity\Company;

/**
 * Default self-hosted implementation: there is no subscription concept, so
 * every company is treated as paid and no additional gating is applied.
 * On SaaS this is overridden by {@see \SolidInvoice\SaasBundle\Service\SubscriptionEligibility}.
 */
final class NullPaidSubscriptionGate implements PaidSubscriptionGateInterface
{
    #[Override]
    public function isPaid(Company $company): bool
    {
        return true;
    }

    #[Override]
    public function isActive(Company $company): bool
    {
        return true;
    }
}
