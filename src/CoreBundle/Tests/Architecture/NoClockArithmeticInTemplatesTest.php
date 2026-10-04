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

namespace SolidInvoice\CoreBundle\Tests\Architecture;

use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Guards the SOL-309 standing constraint: overdue and expiry status, and any
 * "N days overdue/expired" count, are domain facts computed once and read by
 * templates. No template, macro or view may re-derive the boundary from the
 * clock.
 *
 * SOL-306 found the same wrong expression, (dueTimestamp - now) / 86400,
 * copied into four templates, where it survived for years because nothing
 * failed. This test is the recurrence guard for that defect class.
 */
final class NoClockArithmeticInTemplatesTest extends TestCase
{
    /**
     * Each entry is [description, regex]. Matched line by line against every
     * Twig view so a failure can report the exact file, line and text.
     *
     * @var list<array{0: string, 1: string}>
     */
    private const array FORBIDDEN_PATTERNS = [
        ["a Unix timestamp via date('U')", '/\|\s*date\(\s*([\'"])U\1\s*\)/'],
        ['the hard-coded seconds-per-day constant (86400)', '/\b86400\b/'],
        ['a comparison operator against date()', '/(<=|>=|==|!=|<|>)\s*date\(\s*\)/'],
        ['date() against a comparison operator', '/date\(\s*\)\s*(<=|>=|==|!=|<|>)/'],
        ["a comparison operator against 'now'/\"now\"", '/(<=|>=|==|!=|<|>)\s*([\'"])now\2/'],
        ["'now'/\"now\" against a comparison operator", '/([\'"])now\1\s*(<=|>=|==|!=|<|>)/'],
        ['date_diff()', '/date_diff\(/'],
        ['.diff() against now or date()', '/\.diff\s*\(\s*(now|date\(\s*\))\s*\)/'],
        ['now.diff()', '/\bnow\s*\.\s*diff\s*\(/'],
        ['date().diff()', '/date\(\s*\)\s*\.\s*diff\s*\(/'],
    ];

    public function testNoTemplateDerivesADateBoundaryFromTheClock(): void
    {
        $violations = [];

        foreach ($this->twigFiles() as $file) {
            foreach ($this->matchForbiddenPatterns($this->lines($file)) as $violation) {
                $violations[] = sprintf('%s:%d — %s (matched "%s")', $file->getPathname(), $violation['line'], $violation['description'], $violation['match']);
            }
        }

        self::assertSame(
            [],
            $violations,
            'Found clock-derived date boundaries in Twig templates. Compute the boundary once in the domain ' .
            '(see SolidInvoice\\CoreBundle\\Twig\\Extension\\DueDateExtension) and read it from the template ' .
            "instead:\n" . implode("\n", $violations),
        );
    }

    public function testDisplayFormattingOfNowIsNotFlagged(): void
    {
        $lines = $this->lines(new SplFileInfo($this->srcRoot() . '/InvoiceBundle/Resources/views/Components/CreateRecurringInvoice.html.twig'));

        self::assertStringContainsString("'now'|date('F')", $lines[275]);
        self::assertStringContainsString("'now'|date('Y')", $lines[279]);

        self::assertSame([], $this->matchForbiddenPatterns([275 => $lines[275], 279 => $lines[279]]));
    }

    public function testDiffBetweenTwoStoredDatesIsNotFlagged(): void
    {
        $lines = $this->lines(new SplFileInfo($this->srcRoot() . '/SaasBundle/Resources/views/subscription/trial_expired.html.twig'));

        self::assertStringContainsString('subscription.startDate.diff(subscription.endDate)', $lines[24]);

        self::assertSame([], $this->matchForbiddenPatterns([24 => $lines[24]]));
    }

    public function testBandingADomainComputedFigureIsNotFlagged(): void
    {
        $lines = $this->lines(new SplFileInfo($this->srcRoot() . '/InvoiceBundle/Resources/views/Pdf/invoice.html.twig'));
        $bandingLines = array_filter($lines, static fn (string $line): bool => str_contains($line, 'daysDiff'));

        self::assertNotEmpty($bandingLines);
        self::assertSame([], $this->matchForbiddenPatterns($bandingLines));
    }

    /**
     * @param array<int, string> $lines keyed by zero-based line number
     *
     * @return list<array{line: int, description: string, match: string}>
     */
    private function matchForbiddenPatterns(array $lines): array
    {
        $violations = [];

        foreach ($lines as $index => $line) {
            foreach (self::FORBIDDEN_PATTERNS as [$description, $pattern]) {
                if (preg_match($pattern, $line, $matches) === 1) {
                    $violations[] = [
                        'line' => $index + 1,
                        'description' => $description,
                        'match' => trim($matches[0]),
                    ];
                }
            }
        }

        return $violations;
    }

    /**
     * @return array<int, string> the file's lines, keyed by zero-based line number
     */
    private function lines(SplFileInfo $file): array
    {
        return explode("\n", (string) file_get_contents($file->getPathname()));
    }

    /**
     * @return iterable<SplFileInfo>
     */
    private function twigFiles(): iterable
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->srcRoot(), FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.twig') && str_contains($file->getPathname(), '/Resources/views/')) {
                yield $file;
            }
        }
    }

    private function srcRoot(): string
    {
        return dirname(__DIR__, 3);
    }
}
