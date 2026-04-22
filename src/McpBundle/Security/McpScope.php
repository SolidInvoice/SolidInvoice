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

namespace SolidInvoice\McpBundle\Security;

enum McpScope: string
{
    case Read = 'mcp:read';
    case Write = 'mcp:write';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }

    /**
     * Write scope implies read; check whether the given granted scopes satisfy a required scope.
     *
     * @param list<string> $grantedScopes
     */
    public static function satisfies(array $grantedScopes, self $required): bool
    {
        if (\in_array($required->value, $grantedScopes, true)) {
            return true;
        }

        if ($required === self::Read && \in_array(self::Write->value, $grantedScopes, true)) {
            return true;
        }

        return false;
    }
}
