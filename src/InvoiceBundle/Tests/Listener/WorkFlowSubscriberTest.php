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

namespace SolidInvoice\InvoiceBundle\Tests\Listener;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use PHPUnit\Framework\TestCase;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\CoreBundle\Test\Traits\DoctrineTestTrait;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Listener\WorkFlowSubscriber;
use SolidInvoice\NotificationBundle\Notification\NotificationManager;
use Symfony\Component\Workflow\Event\Event;
use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\Transition;
use Symfony\Component\Workflow\WorkflowInterface;

class WorkFlowSubscriberTest extends TestCase
{
    use DoctrineTestTrait;
    use MockeryPHPUnitIntegration;

    public function testInvoicePaid(): void
    {
        $notification = M::mock(NotificationManager::class);
        $notification->shouldReceive('sendNotification')
            ->once();

        $subscriber = new WorkFlowSubscriber($this->registry, $notification);

        $invoice = new Invoice();
        $invoice->setBalance(1200);
        $invoice->setClient((new Client())->setName('Test')->setCurrencyCode('USD'));
        $invoice->setStatus('pending');

        $subscriber->onWorkflowTransitionApplied(new Event($invoice, new Marking(['pending' => 1]), new Transition('pay', 'pending', 'paid'), M::mock(WorkflowInterface::class)));
        self::assertNotNull($invoice->getPaidDate());
        self::assertEquals($invoice, $this->em->getRepository(Invoice::class)->find($invoice->getId()));
    }

    public function testInvoiceArchive(): void
    {
        $notification = M::mock(NotificationManager::class);
        $notification->shouldReceive('sendNotification')
            ->once();

        $subscriber = new WorkFlowSubscriber($this->registry, $notification);

        $invoice = new Invoice();
        $invoice->setBalance(1200);
        $invoice->setClient((new Client())->setName('Test')->setCurrencyCode('USD'));
        $invoice->setStatus('pending');

        $subscriber->onWorkflowTransitionApplied(new Event($invoice, new Marking(['pending' => 1]), new Transition('archive', 'pending', 'archived'), M::mock(WorkflowInterface::class)));

        self::assertTrue($invoice->isArchived());
        self::assertSame($invoice, $this->em->getRepository(Invoice::class)->find($invoice->getId()));
    }
}
