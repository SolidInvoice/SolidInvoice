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

use Psr\Clock\ClockInterface;
use SolidInvoice\ClientBundle\Repository\ClientRepository;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Repository\InvoiceRepository;
use SolidInvoice\SaasBundle\Onboarding\OnboardingContext;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Translation\TranslatorInterface;

final class TrialAboutToEndStep extends AbstractOnboardingEmailStep
{
    public function __construct(
        TranslatorInterface $translator,
        private readonly ClockInterface $clock,
        private readonly ClientRepository $clientRepository,
        private readonly InvoiceRepository $invoiceRepository,
        #[Autowire(env: 'SOLIDINVOICE_SAAS_ONBOARDING_COUPON_CODE')]
        private readonly string $couponCode = '',
    ) {
        parent::__construct($translator);
    }

    public static function key(): string
    {
        return 'trial_about_to_end';
    }

    public static function priority(): int
    {
        return 50;
    }

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

    protected function templateContext(OnboardingContext $context): array
    {
        $daysRemaining = $this->calculateDaysRemaining($context);

        return parent::templateContext($context) + [
            'days_remaining' => $daysRemaining,
            'coupon_code' => $this->couponCode,
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
