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

namespace SolidInvoice\McpBundle\Mcp\Tool;

use InvalidArgumentException;
use Mcp\Exception\ToolCallException;
use Symfony\Component\Uid\Ulid;

/**
 * Parses ULIDs from tool inputs. IDs are emitted as base58 ({@see Ulid::toBase58()}),
 * but any form {@see Ulid::fromString()} understands is accepted: base58, Crockford
 * base32 (26 chars) and RFC 4122 (36 chars with hyphens).
 */
final class UlidParser
{
    public static function parse(string $value, string $fieldName = 'id'): Ulid
    {
        try {
            return Ulid::fromString($value);
        } catch (InvalidArgumentException) {
            throw new ToolCallException(sprintf('Invalid %s: %s must be a ULID.', $fieldName, $value));
        }
    }
}
