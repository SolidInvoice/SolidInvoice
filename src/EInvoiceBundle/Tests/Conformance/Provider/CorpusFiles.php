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

namespace SolidInvoice\EInvoiceBundle\Tests\Conformance\Provider;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use function is_dir;
use function preg_match;
use function scandir;
use function sort;
use function str_starts_with;
use function strtolower;

/**
 * Filesystem helpers shared by the conformance data providers.
 */
final class CorpusFiles
{
    /**
     * Every file under $directory, found recursively, whose extension starts with "xm",
     * case-insensitively. This catches both the ".xml"/".XML" spelling and the two files the
     * peppol-bis-3 corpus ships with a typo'd ".xm" extension.
     *
     * @return list<string>
     */
    public static function xml(string $directory): array
    {
        if (! is_dir($directory)) {
            return [];
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
        );

        $files = [];

        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isFile() && str_starts_with(strtolower($fileInfo->getExtension()), 'xm')) {
                $files[] = $fileInfo->getPathname();
            }
        }

        sort($files);

        return $files;
    }

    /**
     * Directories directly under $base whose name matches $pattern, keyed by full path, with
     * the named "suffix" capture group of the match as the value.
     *
     * @return iterable<string, string>
     */
    public static function directories(string $base, string $pattern): iterable
    {
        if (! is_dir($base)) {
            return;
        }

        $names = scandir($base);

        if (false === $names) {
            return;
        }

        sort($names);

        foreach ($names as $name) {
            if ('.' === $name || '..' === $name) {
                continue;
            }

            $path = $base . '/' . $name;

            if (! is_dir($path)) {
                continue;
            }

            if (1 !== preg_match($pattern, $name, $matches) || ! isset($matches['suffix'])) {
                continue;
            }

            yield $path => $matches['suffix'];
        }
    }
}
