<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\ApiBundle\Event\Listener;

use ApiPlatform\Core\EventListener\EventPriorities;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Model\Graph;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Workflow\StateMachine;

class InvoiceCreateListener implements EventSubscriberInterface
{
    /**
     * @var StateMachine
     */
    private $stateMachine;

    public function __construct(StateMachine $stateMachine)
    {
        $this->stateMachine = $stateMachine;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => [['setInvoiceStatus', EventPriorities::PRE_WRITE]],
        ];
    }

    public function setInvoiceStatus(\Symfony\Component\HttpKernel\Event\ViewEvent $event)
    {
        $invoice = $event->getControllerResult();
        $method = $event->getRequest()->getMethod();

        if (!$event->isMasterRequest() || !$invoice instanceof Invoice || $invoice->getStatus() || Request::METHOD_POST !== $method) {
            return;
        }

        $this->stateMachine->apply($invoice, Graph::TRANSITION_NEW);
    }
}
