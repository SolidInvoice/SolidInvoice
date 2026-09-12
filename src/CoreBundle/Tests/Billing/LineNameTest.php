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

namespace SolidInvoice\CoreBundle\Tests\Billing;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Billing\LineName;
use function mb_strlen;
use function str_repeat;

final class LineNameTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function descriptions(): iterable
    {
        yield 'short single line' => ['Website design', 'Website design'];
        yield 'surrounding whitespace' => ["  Website design \n", 'Website design'];
        yield 'multi line keeps the first line' => ["Website design\nIncluding two rounds of revisions.", 'Website design'];
        yield 'windows line endings' => ["Website design\r\nWith revisions.", 'Website design'];
        yield 'lone carriage returns' => ["Website design\rWith revisions.", 'Website design'];
        yield 'leading blank lines are skipped' => ["\n \nWebsite design\nWith revisions.", 'Website design'];
        yield 'blank' => ['   ', ''];
        yield 'empty' => ['', ''];
    }

    #[DataProvider('descriptions')]
    public function testItNamesALineAfterTheFirstLineOfItsDescription(string $description, string $expected): void
    {
        self::assertSame($expected, LineName::fromDescription($description));
    }

    /**
     * A description that opens with a blank line still has to name its line: `name` is
     * `NotBlank`, and onboarding persists the invoice it builds without ever validating it.
     */
    public function testALeadingBlankLineDoesNotLeaveTheLineUnnamed(): void
    {
        self::assertSame('Website design', LineName::fromDescription("\nWebsite design"));
    }

    public function testItTruncatesALineTooLongForTheColumn(): void
    {
        $name = LineName::fromDescription(str_repeat('a', LineName::MAX_LENGTH + 50));

        self::assertSame(LineName::MAX_LENGTH, mb_strlen($name));
        self::assertStringEndsWith('…', $name);
    }

    public function testItLeavesALineExactlyTheColumnWidthAlone(): void
    {
        $description = str_repeat('a', LineName::MAX_LENGTH);

        self::assertSame($description, LineName::fromDescription($description));
    }

    /**
     * Counted in characters, not bytes, or a multi-byte description would truncate to more
     * than the column holds.
     */
    public function testItCountsCharactersRatherThanBytes(): void
    {
        $name = LineName::fromDescription(str_repeat('é', LineName::MAX_LENGTH));

        self::assertSame(str_repeat('é', LineName::MAX_LENGTH), $name);
    }
}
