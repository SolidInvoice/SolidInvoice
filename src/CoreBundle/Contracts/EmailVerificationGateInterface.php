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

interface EmailVerificationGateInterface
{
    /**
     * True when the current authenticated user (and their currently selected
     * company) should be blocked from outbound actions because the user's
     * email is not verified. False on self-hosted instances and for verified
     * users.
     */
    public function isGated(): bool;

    /**
     * True when a given company has zero verified users and a SaaS
     * subscription exists. Used by public view-link gating where there is no
     * authenticated user. False on self-hosted instances.
     */
    public function isCompanyGated(Company $company): bool;

    /**
     * Localised friendly message in the form
     * "Please verify your email address before {action}."
     * Returns an empty string on the null implementation.
     */
    public function reason(string $action): string;
}
