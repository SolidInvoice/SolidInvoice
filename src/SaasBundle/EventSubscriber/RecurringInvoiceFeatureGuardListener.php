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

namespace SolidInvoice\SaasBundle\EventSubscriber;

use SolidInvoice\CoreBundle\Feature\UpgradePromptProvider;
use SolidInvoice\SaasBundle\Feature\Feature;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Workflow\Event\GuardEvent;
use Symfony\Component\Workflow\TransitionBlocker;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Blocks the recurring-invoice `activate` workflow transition when the
 * SaaS `recurring_invoices` feature is unavailable on the current plan.
 *
 * Parallel to {@see RecurringInvoiceVerificationGuardListener}, which handles
 * email-verification gating; both guard listeners can fire for the same
 * transition without interfering with each other.
 * @see \SolidInvoice\SaasBundle\Tests\EventSubscriber\RecurringInvoiceFeatureGuardListenerTest
 */
final readonly class RecurringInvoiceFeatureGuardListener
{
    public function __construct(
        private FeatureGate $featureGate,
        private UpgradePromptProvider $upgradePromptProvider,
        private TranslatorInterface $translator,
    ) {
    }

    #[AsEventListener('workflow.recurring_invoice.guard.activate')]
    public function onGuardActivate(GuardEvent $event): void
    {
        if ($this->featureGate->isEnabled(Feature::RecurringInvoices->value)) {
            return;
        }

        $event->addTransitionBlocker(
            TransitionBlocker::createUnknown(
                $this->buildReason(),
            ),
        );
    }

    private function buildReason(): string
    {
        $planLabel = $this->upgradePromptProvider->menuLabel(Feature::RecurringInvoices->value);

        if ($planLabel === null) {
            return $this->translator->trans('Recurring invoices are not available on your current plan.');
        }

        return $this->translator->trans(
            'Recurring invoices are not available on your current plan. Upgrade to %plan% to activate this recurring invoice.',
            ['%plan%' => $planLabel],
        );
    }
}
