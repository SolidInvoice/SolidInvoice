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

namespace SolidInvoice\EInvoiceBundle\Tests\Model;

use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * ADR §4.2: monetary values under `src/EInvoiceBundle/` never become a `float`, outside the
 * boundary where a third-party syntax writer forces one. Without this mechanical check the rule
 * rots across the roughly 40 monetary business terms the semantic model carries.
 */
final class NoFloatConversionTest extends TestCase
{
    private const string BUNDLE_ROOT = __DIR__ . '/../../';

    private const string ALLOWED_SUBDIRECTORY = 'Syntax';

    /**
     * @var list<string>
     */
    private const array FORBIDDEN_PATTERNS = ['toFloat(', '(float)', 'floatval('];

    public function testNoFloatConversionOutsideSyntax(): void
    {
        $violations = [];
        $visitedFiles = 0;

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(self::BUNDLE_ROOT, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $realPath = $file->getRealPath();
            \assert($realPath !== false);

            if ($realPath === realpath(__FILE__)) {
                continue;
            }

            $relativePath = str_replace('\\', '/', substr($realPath, strlen((string) realpath(self::BUNDLE_ROOT)) + 1));

            if (str_starts_with($relativePath, self::ALLOWED_SUBDIRECTORY . '/')) {
                continue;
            }

            ++$visitedFiles;

            $contents = file_get_contents($realPath);
            \assert($contents !== false);

            foreach (self::FORBIDDEN_PATTERNS as $pattern) {
                if (str_contains($contents, $pattern)) {
                    $violations[] = sprintf('%s contains forbidden pattern "%s"', $relativePath, $pattern);
                }
            }
        }

        self::assertGreaterThan(0, $visitedFiles, sprintf('Expected to scan at least one file under "%s"; the scan root is misconfigured.', self::BUNDLE_ROOT));
        self::assertSame([], $violations, "Float conversion found outside Syntax/:\n" . implode("\n", $violations));
    }
}
