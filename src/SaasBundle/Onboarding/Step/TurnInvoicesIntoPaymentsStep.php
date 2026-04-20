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

use SolidInvoice\PaymentBundle\Repository\PaymentRepository;
use SolidInvoice\SaasBundle\Onboarding\OnboardingContext;
use Symfony\Contracts\Translation\TranslatorInterface;

final class TurnInvoicesIntoPaymentsStep extends AbstractOnboardingEmailStep
{
    public function __construct(
        TranslatorInterface $translator,
        private readonly PaymentRepository $paymentRepository,
    ) {
        parent::__construct($translator);
    }

    public static function key(): string
    {
        return 'turn_invoices_into_payments';
    }

    public static function priority(): int
    {
        return 70;
    }

    public function shouldSend(OnboardingContext $context): bool
    {
        return $this->paymentRepository->count(['company' => $context->company]) === 0;
    }
}
