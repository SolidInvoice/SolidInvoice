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
use SolidInvoice\InvoiceBundle\Repository\InvoiceRepository;
use SolidInvoice\SaasBundle\Onboarding\OnboardingContext;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsTaggedItem(priority: 80)]
final class SendFirstInvoiceStep extends AbstractOnboardingEmailStep
{
    public function __construct(
        TranslatorInterface $translator,
        private readonly InvoiceRepository $invoiceRepository,
    ) {
        parent::__construct($translator);
    }

    public static function key(): string
    {
        return 'send_first_invoice';
    }

    public static function priority(): int
    {
        return 80;
    }

    #[Override]
    public function shouldSend(OnboardingContext $context): bool
    {
        return $this->invoiceRepository->count(['company' => $context->company]) === 0;
    }
}
