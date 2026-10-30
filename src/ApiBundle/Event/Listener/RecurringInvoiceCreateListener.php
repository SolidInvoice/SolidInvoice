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

namespace SolidInvoice\ApiBundle\Event\Listener;

use ApiPlatform\Symfony\EventListener\EventPriorities;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\InvoiceBundle\Enum\RecurringInvoiceStatus;
use SolidInvoice\InvoiceBundle\Model\Graph;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Workflow\WorkflowInterface;

class RecurringInvoiceCreateListener
{
    public function __construct(
        private readonly WorkflowInterface $recurringInvoiceStateMachine
    ) {
    }

    #[AsEventListener(event: KernelEvents::VIEW, priority: EventPriorities::PRE_WRITE)]
    public function setRecurringInvoiceStatus(ViewEvent $event): void
    {
        $recurringInvoice = $event->getControllerResult();
        $method = $event->getRequest()->getMethod();

        if (! $recurringInvoice instanceof RecurringInvoice || Request::METHOD_POST !== $method || ! $event->isMainRequest() || $recurringInvoice->getStatus() instanceof RecurringInvoiceStatus) {
            return;
        }

        $this->recurringInvoiceStateMachine->apply($recurringInvoice, Graph::TRANSITION_NEW);
    }
}
