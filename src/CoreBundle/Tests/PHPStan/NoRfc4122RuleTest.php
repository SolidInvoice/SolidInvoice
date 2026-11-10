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

namespace SolidInvoice\CoreBundle\Tests\PHPStan;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use SolidInvoice\CoreBundle\PHPStan\NoRfc4122Rule;

/**
 * @extends RuleTestCase<NoRfc4122Rule>
 */
final class NoRfc4122RuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NoRfc4122Rule();
    }

    public function testFlagsRfc4122AndAllowsBase58(): void
    {
        $message = 'Use toBase58() instead of toRfc4122(): IDs are always base58 (AGENTS.md §9).';

        $this->analyse([__DIR__ . '/data/rfc4122.php'], [
            [$message, 22],
            [$message, 27],
            [$message, 32],
            [$message, 37],
        ]);
    }
}
