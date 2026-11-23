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

namespace SolidInvoice\EInvoiceBundle\Tests\Conformance\Engine;

/**
 * Nothing registers an engine yet, so create() returns null until SOL-108 (GH #2673) wires a
 * real ConformanceEngine (from a bootstrap step, calling register()). Every conformance test
 * opens by calling create() and reporting incomplete, not passed, when it returns null.
 */
final class ConformanceEngineFactory
{
    private static ?ConformanceEngine $engine = null;

    public static function create(): ?ConformanceEngine
    {
        return self::$engine;
    }

    public static function register(ConformanceEngine $engine): void
    {
        self::$engine = $engine;
    }
}
