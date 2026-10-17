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

/**
 * Asks for feedback once the user has really used the app: a week in, with at
 * least one invoice sent, so there is something to have an opinion about.
 */
#[AsTaggedItem(priority: 55)]
final class FeedbackStep extends AbstractOnboardingEmailStep
{
    private const int AFTER_HOURS = 7 * 24;

    public function __construct(
        TranslatorInterface $translator,
        private readonly InvoiceRepository $invoiceRepository,
    ) {
        parent::__construct($translator);
    }

    public static function key(): string
    {
        return 'feedback';
    }

    public static function priority(): int
    {
        return 55;
    }

    #[Override]
    public function shouldSend(OnboardingContext $context): bool
    {
        return $context->hoursSinceTrialStart() >= self::AFTER_HOURS
            && $this->invoiceRepository->countIssued($context->company) > 0;
    }
}
