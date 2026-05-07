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
use SolidInvoice\CoreBundle\Contracts\EmailVerificationGateInterface;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\SaasBundle\EventSubscriber\RecurringInvoiceVerificationGuardListener;
use Symfony\Component\Workflow\Event\GuardEvent;
use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\Transition;
use Symfony\Component\Workflow\WorkflowInterface;

final class RecurringInvoiceVerificationGuardListenerTest extends TestCase
{
    public function testBlocksTransitionWhenGated(): void
    {
        $gate = $this->createMock(EmailVerificationGateInterface::class);
        $gate->method('isGated')->willReturn(true);
        $gate->method('reason')->willReturn('Please verify your email address before activating this recurring invoice.');

        $event = new GuardEvent(
            new RecurringInvoice(),
            new Marking(),
            new Transition('activate', 'draft', 'active'),
            $this->createMock(WorkflowInterface::class),
        );

        (new RecurringInvoiceVerificationGuardListener($gate))->onGuardActivate($event);

        self::assertTrue($event->isBlocked());
        $blockers = iterator_to_array($event->getTransitionBlockerList());
        self::assertCount(1, $blockers);
        self::assertSame(
            'Please verify your email address before activating this recurring invoice.',
            $blockers[0]->getMessage(),
        );
    }

    public function testAllowsTransitionWhenNotGated(): void
    {
        $gate = $this->createMock(EmailVerificationGateInterface::class);
        $gate->method('isGated')->willReturn(false);

        $event = new GuardEvent(
            new RecurringInvoice(),
            new Marking(),
            new Transition('activate', 'draft', 'active'),
            $this->createMock(WorkflowInterface::class),
        );

        (new RecurringInvoiceVerificationGuardListener($gate))->onGuardActivate($event);

        self::assertFalse($event->isBlocked());
    }
}
