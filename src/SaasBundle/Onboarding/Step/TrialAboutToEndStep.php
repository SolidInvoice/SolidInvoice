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
use Psr\Clock\ClockInterface;
use SolidInvoice\ClientBundle\Repository\ClientRepository;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Repository\InvoiceRepository;
use SolidInvoice\SaasBundle\Onboarding\OnboardingContext;
use SolidInvoice\SaasBundle\Service\BillingMode;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsTaggedItem(priority: 50)]
final class TrialAboutToEndStep extends AbstractOnboardingEmailStep
{
    public function __construct(
        TranslatorInterface $translator,
        private readonly ClockInterface $clock,
        private readonly ClientRepository $clientRepository,
        private readonly InvoiceRepository $invoiceRepository,
        private readonly BillingMode $billingMode,
    ) {
        parent::__construct($translator);
    }

    /**
     * A paid trial already has a card on file, so warning the user that it is
     * about to end — and offering a coupon to convert — is meaningless. The
     * subscription simply bills at the end of the trial.
     */
    #[Override]
    public function shouldSend(OnboardingContext $context): bool
    {
        return ! $this->billingMode->requiresCardForTrial() && parent::shouldSend($context);
    }

    public static function key(): string
    {
        return 'trial_about_to_end';
    }

    public static function priority(): int
    {
        return 50;
    }

    #[Override]
    public function createEmail(OnboardingContext $context): TemplatedEmail
    {
        $email = parent::createEmail($context);

        $daysRemaining = $this->calculateDaysRemaining($context);

        $email->subject($this->translator->trans(
            'onboarding.trial_about_to_end.subject',
            ['%days%' => $daysRemaining, '%count%' => $daysRemaining],
            'email',
        ));

        return $email;
    }

    #[Override]
    protected function templateContext(OnboardingContext $context): array
    {
        $daysRemaining = $this->calculateDaysRemaining($context);

        return parent::templateContext($context) + [
            'days_remaining' => $daysRemaining,
            'coupon_code' => $this->billingMode->couponCode(),
            'coupon_percent' => $this->billingMode->couponPercent(),
            'usage_clients' => $this->clientRepository->getTotalClients(),
            'usage_invoices' => $this->invoiceRepository->getTotalInvoices(),
            'usage_collected' => $this->invoiceRepository->getCountByStatus(InvoiceStatus::Paid),
        ];
    }

    private function calculateDaysRemaining(OnboardingContext $context): int
    {
        $now = $this->clock->now();

        return $now < $context->trialEnd
            ? (int) ceil(($context->trialEnd->getTimestamp() - $now->getTimestamp()) / 86400)
            : 0;
    }
}
