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

namespace SolidInvoice\CoreBundle\Twig\Extension;

use const DIRECTORY_SEPARATOR;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use function file_get_contents;
use function ltrim;
use function realpath;
use function rtrim;
use function str_starts_with;

/**
 * @see \SolidInvoice\CoreBundle\Tests\Twig\Extension\FileExtensionTest
 */
final class FileExtension extends AbstractExtension
{
    public function __construct(
        private readonly string $projectDir
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('file', $this->readFile(...), ['is_safe' => ['css', 'html']]),
        ];
    }

    public function readFile(string $file): string
    {
        $publicDir = realpath($this->projectDir . DIRECTORY_SEPARATOR . 'public');

        if (false === $publicDir) {
            return '';
        }

        $relative = ltrim($file, '\\/');
        $candidate = $publicDir . DIRECTORY_SEPARATOR . $relative;
        $resolved = realpath($candidate);

        if (false === $resolved) {
            return '';
        }

        // Ensure the resolved path is still under the public directory
        // (prevents `..` traversal escaping into project files).
        $publicPrefix = rtrim($publicDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (! str_starts_with($resolved, $publicPrefix)) {
            return '';
        }

        $contents = file_get_contents($resolved);

        return false === $contents ? '' : $contents;
    }
}
