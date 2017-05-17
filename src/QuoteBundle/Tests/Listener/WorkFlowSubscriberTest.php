<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\QuoteBundle\Tests\Listener;

use CSBill\CoreBundle\Test\Traits\DoctrineTestTrait;
use CSBill\InvoiceBundle\Entity\Invoice;
use CSBill\InvoiceBundle\Manager\InvoiceManager;
use CSBill\QuoteBundle\Entity\Quote;
use CSBill\QuoteBundle\Listener\WorkFlowSubscriber;
use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Workflow\Event\Event;
use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\StateMachine;
use Symfony\Component\Workflow\Transition;

class WorkFlowSubscriberTest extends TestCase
{
    use DoctrineTestTrait,
        MockeryPHPUnitIntegration;

    protected function setUp()
    {
        $this->setupDoctrine();
    }

    public function testOnQuoteAccepted()
    {
        $quote = new Quote();
        $invoice = new Invoice();

        $invoiceManager = M::mock(InvoiceManager::class);

        $invoiceManager->shouldReceive('createFromQuote')
            ->with($quote)
            ->andReturn($invoice);

        $stateMachine = M::mock(StateMachine::class);

        $stateMachine->shouldReceive('apply')
            ->with($invoice, 'new');

        $stateMachine->shouldReceive('apply')
            ->with($invoice, 'accept');

        $subscriber = new WorkFlowSubscriber($this->registry, $invoiceManager, $stateMachine);

        $subscriber->onQuoteAccepted(new Event($quote, new Marking(['pending' => 1]), new Transition('archive', 'pending', 'archived')));
    }

    public function testOnWorkflowTransitionApplied()
    {
        $quote = (new Quote())->setStatus('pending');

        $invoiceManager = M::mock(InvoiceManager::class);
        $stateMachine = M::mock(StateMachine::class);

        $subscriber = new WorkFlowSubscriber($this->registry, $invoiceManager, $stateMachine);

        $subscriber->onWorkflowTransitionApplied(new Event($quote, new Marking(['pending' => 1]), new Transition('archive', 'pending', 'archived')));

        $this->assertTrue($quote->isArchived());
        $this->assertSame($quote, $this->em->getRepository('CSBillQuoteBundle:Quote')->find(1));
    }

    public function getEntityNamespaces()
    {
        return [
            'CSBillQuoteBundle' => 'CSBill\\QuoteBundle\\Entity',
        ];
    }

    public function getEntities()
    {
        return [
            'CSBillQuoteBundle:Quote',
        ];
    }
}
