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

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class FileExtension extends AbstractExtension
{
    public function __construct(private readonly string $projectDir)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('file', fn ($file) => file_get_contents($this->projectDir . '/public/' . ltrim((string) $file, '\\')), ['is_safe' => ['css', 'html']]),
        ];
    }
}
