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

namespace SolidInvoice\CoreBundle\Feature;

use Override;

final class NullUpgradePromptProvider implements UpgradePromptProvider
{
    #[Override]
    public function prompt(string $featureKey): string
    {
        return '';
    }

    #[Override]
    public function menuLabel(string $featureKey): ?string
    {
        return null;
    }
}
