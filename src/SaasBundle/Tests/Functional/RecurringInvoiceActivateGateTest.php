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

use SolidInvoice\CoreBundle\Contracts\EmailVerificationGateInterface;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\InvoiceBundle\Enum\RecurringInvoiceStatus;
use SolidInvoice\SaasBundle\EventSubscriber\RecurringInvoiceVerificationGuardListener;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Workflow\DefinitionBuilder;
use Symfony\Component\Workflow\MarkingStore\MethodMarkingStore;
use Symfony\Component\Workflow\StateMachine;
use Symfony\Component\Workflow\Transition;

/**
 * Verifies the SaaS email-verification gate blocks the recurring invoice
 * `activate` workflow transition (causing workflow_can() to return false)
 * when the gate is engaged, and allows it through otherwise.
 *
 * @group functional
 */
final class RecurringInvoiceActivateGateTest extends \PHPUnit\Framework\TestCase
{
    public function testActivateIsBlockedWhenGated(): void
    {
        $workflow = $this->buildWorkflow(gated: true);

        $invoice = new RecurringInvoice();
        $invoice->setStatus(RecurringInvoiceStatus::Draft);

        self::assertFalse($workflow->can($invoice, 'activate'));
    }

    public function testActivateIsAllowedWhenNotGated(): void
    {
        $workflow = $this->buildWorkflow(gated: false);

        $invoice = new RecurringInvoice();
        $invoice->setStatus(RecurringInvoiceStatus::Draft);

        self::assertTrue($workflow->can($invoice, 'activate'));
    }

    private function buildWorkflow(bool $gated): StateMachine
    {
        $gate = $this->createMock(EmailVerificationGateInterface::class);
        $gate->method('isGated')->willReturn($gated);
        $gate->method('reason')->willReturn('Please verify your email address before activating this recurring invoice.');

        $listener = new RecurringInvoiceVerificationGuardListener($gate);

        $dispatcher = new EventDispatcher();
        $dispatcher->addListener(
            'workflow.recurring_invoice.guard.activate',
            $listener->onGuardActivate(...),
        );

        $definition = (new DefinitionBuilder())
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
