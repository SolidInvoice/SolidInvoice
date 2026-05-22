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

namespace SolidInvoice\CoreBundle\Config;

use function file_get_contents;

/**
 * Helper trait for config providers that need to read a packaged Twig template
 * file from their bundle's Resources/views directory.
 */
trait LoadsBundleViewTemplate
{
    /**
     * Loads a template file relative to the bundle's Resources/views directory.
     *
     * Returns an empty string when the file cannot be read so the boot/install
     * flow never crashes on a missing template.
     */
    protected function loadBundleViewTemplate(string $bundleViewsDir, string $path): string
    {
        $template = @file_get_contents($bundleViewsDir . '/' . $path);

        return false === $template ? '' : $template;
    }
}
