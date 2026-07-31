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

namespace SolidInvoice;

use Override;
use Symfony\Component\Runtime\SymfonyRuntime;

final class Runtime extends SymfonyRuntime
{
    #[Override]
    protected function resolveType(string $type): mixed
    {
        return match ($type) {
            AppMode::class => AppMode::from($_SERVER['SOLIDINVOICE_PLATFORM'] ?? AppMode::SELF_HOSTED->value),
            default => parent::resolveType($type),
        };
    }
}
