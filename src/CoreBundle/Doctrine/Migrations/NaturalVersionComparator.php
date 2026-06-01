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

namespace SolidInvoice\CoreBundle\Doctrine\Migrations;

use Doctrine\Migrations\Version\Comparator;
use Doctrine\Migrations\Version\Version;
use function strnatcmp;

/**
 * Orders migration versions using a natural ordering instead of the default
 * alphabetical ({@see \Doctrine\Migrations\Version\AlphabeticalComparator}) one.
 *
 * The migration naming convention `Version{version}_{part}` produces multi-digit
 * parts (e.g. `Version30000_10`) that sort incorrectly with strcmp — `_10` would
 * run right after `_1`, before `_2`. A natural comparison keeps the parts in their
 * intended numeric order (`_9` before `_10`).
 */
final class NaturalVersionComparator implements Comparator
{
    public function compare(Version $a, Version $b): int
    {
        return strnatcmp((string) $a, (string) $b);
    }
}
