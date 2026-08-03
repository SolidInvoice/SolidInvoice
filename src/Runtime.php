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

use InvalidArgumentException;
use Override;
use Symfony\Component\Runtime\SymfonyRuntime;
use function array_column;
use function implode;
use function sprintf;

final class Runtime extends SymfonyRuntime
{
    #[Override]
    protected function resolveType(string $type): mixed
    {
        return match ($type) {
            AppMode::class => $this->appMode(),
            default => parent::resolveType($type),
        };
    }

    /**
     * Resolved with tryFrom() rather than from() so a misconfigured SOLIDINVOICE_PLATFORM
     * fails with a message naming the accepted values, instead of a bare ValueError from
     * deep inside the runtime before the application has booted.
     */
    private function appMode(): AppMode
    {
        $platform = (string) ($_SERVER['SOLIDINVOICE_PLATFORM'] ?? '');

        if ('' === $platform) {
            return AppMode::SELF_HOSTED;
        }

        return AppMode::tryFrom($platform) ?? throw new InvalidArgumentException(sprintf(
            'Invalid value "%s" for SOLIDINVOICE_PLATFORM. Expected one of: %s.',
            $platform,
            implode(', ', array_column(AppMode::cases(), 'value')),
        ));
    }
}
