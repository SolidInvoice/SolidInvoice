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
     * The first line of the description, trimmed, and cut to fit the column.
     *
     * The first line rather than the first sentence: a description someone wrote as a
     * heading with detail under it already says where the name ends, and a description
     * written as prose has no line break to find, so it falls back to the truncation.
     */
    public static function fromDescription(string $description): string
    {
        $firstLine = trim(explode("\n", str_replace("\r\n", "\n", $description), 2)[0]);

        if (mb_strlen($firstLine) <= self::MAX_LENGTH) {
            return $firstLine;
        }

        return rtrim(mb_substr($firstLine, 0, self::MAX_LENGTH - mb_strlen(self::ELLIPSIS))) . self::ELLIPSIS;
    }
}
