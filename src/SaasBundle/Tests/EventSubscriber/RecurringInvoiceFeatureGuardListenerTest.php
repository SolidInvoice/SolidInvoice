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

namespace SolidInvoice\SaasBundle\Tests\EventSubscriber;

use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Feature\NullUpgradePromptProvider;
use SolidInvoice\CoreBundle\Feature\UpgradePromptProvider;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\SaasBundle\EventSubscriber\RecurringInvoiceFeatureGuardListener;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;
use SolidWorx\Platform\PlatformBundle\Feature\NoopFeatureGate;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Workflow\Event\GuardEvent;
use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\Transition;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class RecurringInvoiceFeatureGuardListenerTest extends TestCase
{
    public function testBlocksTransitionWhenFeatureDisabledWithPlanLabel(): void
    {
        $featureGate = $this->createStub(FeatureGate::class);
        $featureGate->method('isEnabled')->willReturn(false);

        $upgradePromptProvider = $this->createStub(UpgradePromptProvider::class);
        $upgradePromptProvider->method('menuLabel')
            ->willReturn('Solo');

        $event = $this->buildEvent();
        $listener = new RecurringInvoiceFeatureGuardListener(
            $featureGate,
            $upgradePromptProvider,
            $this->buildTranslator(),
        );

        $listener->onGuardActivate($event);

        self::assertTrue($event->isBlocked());
        $blockers = iterator_to_array($event->getTransitionBlockerList());
        self::assertCount(1, $blockers);
        self::assertStringContainsString('Recurring invoices are not available on your current plan.', $blockers[0]->getMessage());
        self::assertStringContainsString('Solo', $blockers[0]->getMessage());
    }

    public function testBlocksTransitionWhenFeatureDisabledWithoutPlanLabel(): void
    {
        $featureGate = $this->createStub(FeatureGate::class);
        $featureGate->method('isEnabled')->willReturn(false);

        $event = $this->buildEvent();
        $listener = new RecurringInvoiceFeatureGuardListener(
            $featureGate,
            new NullUpgradePromptProvider(),
            $this->buildTranslator(),
        );

        $listener->onGuardActivate($event);

        self::assertTrue($event->isBlocked());
        $blockers = iterator_to_array($event->getTransitionBlockerList());
        self::assertCount(1, $blockers);
        self::assertSame('Recurring invoices are not available on your current plan.', $blockers[0]->getMessage());
    }

    public function testAllowsTransitionWhenFeatureEnabled(): void
    {
        $featureGate = $this->createStub(FeatureGate::class);
        $featureGate->method('isEnabled')->willReturn(true);

        $upgradePromptProvider = $this->createMock(UpgradePromptProvider::class);
        $upgradePromptProvider->expects(self::never())->method('menuLabel');

        $event = $this->buildEvent();
        $listener = new RecurringInvoiceFeatureGuardListener(
            $featureGate,
            $upgradePromptProvider,
            $this->buildTranslator(),
        );

        $listener->onGuardActivate($event);

        self::assertFalse($event->isBlocked());
    }

    public function testSelfHostedAllowsTransition(): void
    {
        $event = $this->buildEvent();
        $listener = new RecurringInvoiceFeatureGuardListener(
            new NoopFeatureGate(),
            new NullUpgradePromptProvider(),
            $this->buildTranslator(),
        );

        $listener->onGuardActivate($event);

        self::assertFalse($event->isBlocked());
    }

    private function buildEvent(): GuardEvent
    {
        return new GuardEvent(
            new RecurringInvoice(),
            new Marking(),
            new Transition('activate', 'draft', 'active'),
            $this->createStub(WorkflowInterface::class),
        );
    }

    private function buildTranslator(): TranslatorInterface
    {
        return new Translator('en');
    }
}
