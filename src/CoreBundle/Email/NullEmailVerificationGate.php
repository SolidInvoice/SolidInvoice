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

namespace SolidInvoice\CoreBundle\Email;

use SolidInvoice\CoreBundle\Contracts\EmailVerificationGateInterface;
use SolidInvoice\CoreBundle\Entity\Company;

final class NullEmailVerificationGate implements EmailVerificationGateInterface
{
    public function isGated(): bool
    {
        return false;
    }

    public function isCompanyGated(Company $company): bool
    {
        return false;
    }

    public function reason(string $action): string
    {
        return '';
    }
}
