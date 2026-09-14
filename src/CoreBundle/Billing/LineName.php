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

namespace SolidInvoice\CoreBundle\Billing;

use function explode;
use function mb_strlen;
use function mb_substr;
use function rtrim;
use function str_replace;
use function trim;

/**
 * How a line's name is derived when only a description was supplied.
 *
 * A caller that predates the name — an API client, an MCP tool, a quote being converted —
 * still sends one field, and a line has to end up with a name either way.
 *
 * @see \SolidInvoice\CoreBundle\Tests\Billing\LineNameTest
 */
final class LineName
{
    /**
     * Matches the `name` column on both line tables.
     */
    public const int MAX_LENGTH = 255;

    private const string ELLIPSIS = '…';

    /**
     * The first line of the description that has anything on it, trimmed, and cut to fit the
     * column.
     *
     * The first line rather than the first sentence: a description someone wrote as a
     * heading with detail under it already says where the name ends, and a description
     * written as prose has no line break to find, so it falls back to the truncation.
     *
     * The first line *with something on it*, because a description that opens with a blank
     * line is still a description — taking the empty string would leave the line with a name
     * that fails its own `NotBlank`, on a path like onboarding that persists without
     * validating.
     */
    public static function fromDescription(string $description): string
    {
        // Lone CR as well as CRLF: a textarea or an older client can still send one, and a
        // description that arrives as a single `\r`-separated run would otherwise be read as
        // one very long line.
        foreach (explode("\n", str_replace(["\r\n", "\r"], "\n", $description)) as $line) {
            $line = trim($line);

            if ($line !== '') {
                return self::truncate($line);
            }
        }

        return '';
    }

    /**
     * Cuts a name to fit the column, marking where it was cut.
     */
    public static function truncate(string $name): string
    {
        if (mb_strlen($name) <= self::MAX_LENGTH) {
            return $name;
        }

        return rtrim(mb_substr($name, 0, self::MAX_LENGTH - mb_strlen(self::ELLIPSIS))) . self::ELLIPSIS;
    }
}
