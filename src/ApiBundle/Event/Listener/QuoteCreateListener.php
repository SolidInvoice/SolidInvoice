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

use ApiPlatform\Core\EventListener\EventPriorities;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Model\Graph;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Workflow\StateMachine;

/**
 * @see \SolidInvoice\ApiBundle\Tests\Event\Listener\QuoteCreateListenerTest
 */
class QuoteCreateListener implements EventSubscriberInterface
{
    /**
     * @var StateMachine
     */
    private $stateMachine;

    public function __construct(StateMachine $stateMachine)
    {
        $this->stateMachine = $stateMachine;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => [['setQuoteStatus', EventPriorities::PRE_WRITE]],
        ];
    }

    public function setQuoteStatus(ViewEvent $event): void
    {
        $quote = $event->getControllerResult();
        $method = $event->getRequest()->getMethod();

        if (! $quote instanceof Quote || Request::METHOD_POST !== $method || ! $event->isMasterRequest() || $quote->getStatus()) {
            return;
        }

        $this->stateMachine->apply($quote, Graph::TRANSITION_NEW);
    }
}
