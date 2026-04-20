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

namespace SolidInvoice\SaasBundle\Tests\Onboarding\Fixtures;

final class StepFirst extends StubStep
{
    public static function key(): string
    {
        return 'first';
    }

    public static function priority(): int
    {
        return 100;
    }
}
