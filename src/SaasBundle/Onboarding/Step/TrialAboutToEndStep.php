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

use Carbon\CarbonImmutable;
use Locale;
use Override;
use SolidInvoice\ClientBundle\Repository\ClientRepository;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Repository\InvoiceRepository;
use SolidInvoice\SaasBundle\Onboarding\OnboardingAudience;
use SolidInvoice\SaasBundle\Onboarding\OnboardingContext;
use SolidInvoice\SaasBundle\Service\BillingMode;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Contracts\Translation\TranslatorInterface;
use function ceil;

/**
 * Four days before the trial ends: a recap of what the user has done, and
 * either what they keep on Free or, when a card is on file, the date it will be
 * charged. A charge nobody saw coming is what turns into a cancellation.
 */
#[AsTaggedItem(priority: 50)]
final class TrialAboutToEndStep extends AbstractOnboardingEmailStep
{
    private const int NOTICE_DAYS = 4;

    public function __construct(
        TranslatorInterface $translator,
        private readonly ClientRepository $clientRepository,
        private readonly InvoiceRepository $invoiceRepository,
        private readonly BillingMode $billingMode,
    ) {
        parent::__construct($translator);
    }

    #[Override]
    public function shouldSend(OnboardingContext $context): bool
    {
        return $context->now >= $context->trialEnd->modify('-' . self::NOTICE_DAYS . ' days');
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
    public static function audience(): OnboardingAudience
    {
        return OnboardingAudience::Trial;
    }

    #[Override]
    public function createEmail(OnboardingContext $context): TemplatedEmail
    {
        $email = parent::createEmail($context);

        $daysRemaining = $this->daysRemaining($context);

        $email->subject($this->translator->trans(
            $this->willCharge($context) ? 'onboarding.trial_about_to_end.charged.subject' : 'onboarding.trial_about_to_end.subject',
            [
                '%days%' => $daysRemaining,
                '%count%' => $daysRemaining,
                '%plan%' => $context->plan->getName(),
                '%date%' => CarbonImmutable::instance($context->trialEnd)->locale(Locale::getDefault())->translatedFormat('F j'),
            ],
            'email',
        ));

        return $email;
    }

    #[Override]
    protected function templateContext(OnboardingContext $context): array
    {
        $willCharge = $this->willCharge($context);

        return parent::templateContext($context) + [
            'days_remaining' => $this->daysRemaining($context),
            'will_charge' => $willCharge,
            // A coupon cannot be redeemed on a subscription that is already billed.
            'coupon_code' => $willCharge ? '' : $this->billingMode->couponCode(),
            'coupon_percent' => $this->billingMode->couponPercent(),
            'usage_clients' => $this->clientRepository->getTotalClients(),
            'usage_invoices' => $this->invoiceRepository->countIssued($context->company),
            'usage_collected' => $this->invoiceRepository->getCountByStatus(InvoiceStatus::Paid),
        ];
    }

    /**
     * A card is on file either because this deployment takes one up front, or
     * because the user added one during a no-card trial.
     */
    private function willCharge(OnboardingContext $context): bool
    {
        return $this->billingMode->requiresCardForTrial() || $context->subscription->isExternallyBilled();
    }

    private function daysRemaining(OnboardingContext $context): int
    {
        return $context->now < $context->trialEnd
            ? (int) ceil(($context->trialEnd->getTimestamp() - $context->now->getTimestamp()) / 86400)
            : 0;
    }
}
