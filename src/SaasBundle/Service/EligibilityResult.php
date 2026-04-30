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

final readonly class EligibilityResult
{
    private function __construct(
        public bool $active,
        public ?string $reason,
    ) {
    }

    public static function active(): self
    {
        return new self(true, null);
    }

    public static function denied(string $reason): self
    {
        return new self(false, $reason);
    }
}
