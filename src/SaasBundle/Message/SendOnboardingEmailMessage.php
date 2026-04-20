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

namespace SolidInvoice\SaasBundle\Message;

use Symfony\Component\Uid\Ulid;

final readonly class SendOnboardingEmailMessage
{
    public function __construct(
        public Ulid $userId,
        public string $stepKey,
    ) {
    }
}
