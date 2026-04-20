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

use SolidInvoice\ClientBundle\Repository\ClientRepository;
use SolidInvoice\SaasBundle\Onboarding\OnboardingContext;
use Symfony\Contracts\Translation\TranslatorInterface;

final class AddFirstClientStep extends AbstractOnboardingEmailStep
{
    public function __construct(
        TranslatorInterface $translator,
        private readonly ClientRepository $clientRepository,
    ) {
        parent::__construct($translator);
    }

    public static function key(): string
    {
        return 'add_first_client';
    }

    public static function priority(): int
    {
        return 90;
    }

    public function shouldSend(OnboardingContext $context): bool
    {
        return $this->clientRepository->count(['company' => $context->company]) === 0;
    }
}
