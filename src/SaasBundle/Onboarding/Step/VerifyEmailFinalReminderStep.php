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

use Override;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(priority: 75)]
final class VerifyEmailFinalReminderStep extends VerifyEmailReminderStep
{
    #[Override]
    public static function key(): string
    {
        return 'verify_email_final_reminder';
    }

    #[Override]
    public static function priority(): int
    {
        return 75;
    }

    #[Override]
    protected static function afterHours(): int
    {
        return 72;
    }

    #[Override]
    protected static function beforeHours(): ?int
    {
        return null;
    }
}
