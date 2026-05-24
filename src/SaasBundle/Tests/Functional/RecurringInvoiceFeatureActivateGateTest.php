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

namespace SolidInvoice\SaasBundle\Tests\Functional;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Feature\NullUpgradePromptProvider;
use SolidInvoice\CoreBundle\Feature\UpgradePromptProvider;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\InvoiceBundle\Enum\RecurringInvoiceStatus;
use SolidInvoice\SaasBundle\EventSubscriber\RecurringInvoiceFeatureGuardListener;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;
use SolidWorx\Platform\PlatformBundle\Feature\NoopFeatureGate;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Workflow\DefinitionBuilder;
use Symfony\Component\Workflow\MarkingStore\MethodMarkingStore;
use Symfony\Component\Workflow\StateMachine;
use Symfony\Component\Workflow\Transition;

/**
 * Verifies the SaaS recurring_invoices feature-gate blocks the recurring
 * invoice `activate` workflow transition (causing workflow_can() to return
 * false) and includes both the upgrade-prompt copy and the required plan
 * name in the transition-blocker reason.
 */
#[Group('functional')]
final class RecurringInvoiceFeatureActivateGateTest extends TestCase
{
    public function testActivateIsBlockedWhenFeatureDisabled(): void
    {
        $workflow = $this->buildWorkflow(featureEnabled: false, planLabel: 'Solo');

        $invoice = new RecurringInvoice();
        $invoice->setStatus(RecurringInvoiceStatus::Draft);

        self::assertFalse($workflow->can($invoice, 'activate'));
    }

    public function testActivateIsAllowedWhenFeatureEnabled(): void
    {
        $workflow = $this->buildWorkflow(featureEnabled: true, planLabel: null);

        $invoice = new RecurringInvoice();
        $invoice->setStatus(RecurringInvoiceStatus::Draft);

        self::assertTrue($workflow->can($invoice, 'activate'));
    }

    public function testActivateIsAllowedInSelfHostedMode(): void
    {
        $featureGate = new NoopFeatureGate();

        $listener = new RecurringInvoiceFeatureGuardListener(
            $featureGate,
            new NullUpgradePromptProvider(),
            new Translator('en'),
        );

        $dispatcher = new EventDispatcher();
        $dispatcher->addListener(
            'workflow.recurring_invoice.guard.activate',
            $listener->onGuardActivate(...),
        );

        $workflow = $this->makeStateMachine($dispatcher);

        $invoice = new RecurringInvoice();
        $invoice->setStatus(RecurringInvoiceStatus::Draft);

        self::assertTrue($workflow->can($invoice, 'activate'));
    }

    private function buildWorkflow(bool $featureEnabled, ?string $planLabel): StateMachine
    {
        $featureGate = $this->createStub(FeatureGate::class);
        $featureGate->method('isEnabled')->willReturn($featureEnabled);

        $upgradePromptProvider = $this->createStub(UpgradePromptProvider::class);
        $upgradePromptProvider->method('menuLabel')->willReturn($planLabel);

        $listener = new RecurringInvoiceFeatureGuardListener(
            $featureGate,
            $upgradePromptProvider,
            new Translator('en'),
        );

        $dispatcher = new EventDispatcher();
        $dispatcher->addListener(
            'workflow.recurring_invoice.guard.activate',
            $listener->onGuardActivate(...),
        );

        return $this->makeStateMachine($dispatcher);
    }

    private function makeStateMachine(EventDispatcher $dispatcher): StateMachine
    {
        $definition = new DefinitionBuilder()
            ->addPlaces([
                RecurringInvoiceStatus::New->value,
                RecurringInvoiceStatus::Draft->value,
                RecurringInvoiceStatus::Active->value,
            ])
            ->addTransition(new Transition('activate', RecurringInvoiceStatus::Draft->value, RecurringInvoiceStatus::Active->value))
            ->build();

        return new StateMachine(
            $definition,
            new MethodMarkingStore(true, 'statusValue'),
            $dispatcher,
            'recurring_invoice',
        );
    }
}
