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
use SolidInvoice\InvoiceBundle\Entity\BaseInvoice;
use SolidInvoice\InvoiceBundle\Model\Graph;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * @see \SolidInvoice\ApiBundle\Tests\Event\Listener\InvoiceCreateListenerTest
 */
class InvoiceCreateListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly WorkflowInterface $invoiceStateMachine
    ) {
    }

    /**
     * @return array<string, list<list<string|int>>>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::VIEW => [['setInvoiceStatus', EventPriorities::PRE_WRITE]],
        ];
    }

    public function setInvoiceStatus(ViewEvent $event): void
    {
        $invoice = $event->getControllerResult();
        $method = $event->getRequest()->getMethod();

        if (! $invoice instanceof BaseInvoice || Request::METHOD_POST !== $method || ! $event->isMainRequest() || $invoice->getStatus()) {
            return;
        }

        $this->invoiceStateMachine->apply($invoice, Graph::TRANSITION_NEW);
    }
}
