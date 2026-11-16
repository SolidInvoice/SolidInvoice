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

namespace SolidInvoice\SaasBundle\Onboarding\Step;

use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(priority: 55)]
final class FeedbackStep extends AbstractOnboardingEmailStep
{
    public static function key(): string
    {
        return 'feedback';
    }

    public static function priority(): int
    {
        return 55;
    }
}
