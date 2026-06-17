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

namespace SolidInvoice\CoreBundle\Contracts;

use SolidInvoice\CoreBundle\Entity\Company;

/**
 * Answers "is this company on a paid plan right now" for dual-platform code
 * (e.g. MCP authorization) that must run on both hosted and self-hosted
 * installs. On SaaS this is backed by the subscription state; on self-hosted
 * the {@see \SolidInvoice\CoreBundle\Subscription\NullPaidSubscriptionGate}
 * default always returns true so no subscription gating is applied.
 */
interface PaidSubscriptionGateInterface
{
    public function isPaid(Company $company): bool;
}
