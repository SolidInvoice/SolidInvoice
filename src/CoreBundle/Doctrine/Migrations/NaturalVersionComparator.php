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

final class NaturalVersionComparator implements Comparator
{
    public function compare(Version $a, Version $b): int
    {
        return strnatcmp((string) $a, (string) $b);
    }
}
