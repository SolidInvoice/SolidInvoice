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
    /**
     * @var string
     */
    private $projectDir;

    public function __construct(string $projectDir)
    {
        $this->projectDir = $projectDir;
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('file', function ($file) {
                return file_get_contents($this->projectDir . '/public/' . ltrim($file, '\\'));
            }, ['is_safe' => ['css', 'html']]),
        ];
    }

    public function getName()
    {
        return 'solidinvoice_core.twig.file';
    }
}
