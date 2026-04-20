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

use SolidInvoice\SaasBundle\Onboarding\OnboardingContext;
use SolidInvoice\SettingsBundle\SystemConfig;
use Symfony\Contracts\Translation\TranslatorInterface;

final class CustomizeLogoStep extends AbstractOnboardingEmailStep
{
    public function __construct(
        TranslatorInterface $translator,
        private readonly SystemConfig $systemConfig,
    ) {
        parent::__construct($translator);
    }

    public static function key(): string
    {
        return 'customize_logo';
    }

    public static function priority(): int
    {
        return 60;
    }

    public function shouldSend(OnboardingContext $context): bool
    {
        $logo = $this->systemConfig->get('system/company/logo');

        return $logo === null || $logo === '';
    }
}
