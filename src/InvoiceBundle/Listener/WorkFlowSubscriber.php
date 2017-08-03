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

namespace CSBill\InvoiceBundle\Listener;

use Carbon\Carbon;
use CSBill\InvoiceBundle\Entity\Invoice;
use CSBill\InvoiceBundle\Model\Graph;
use CSBill\InvoiceBundle\Notification\InvoiceStatusNotification;
use CSBill\NotificationBundle\Notification\NotificationManager;
use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\Event;

class WorkFlowSubscriber implements EventSubscriberInterface
{
    /**
     * @var NotificationManager
     */
    private $notification;

    /**
     * @var ManagerRegistry
     */
    private $registry;

    public function __construct(ManagerRegistry $registry, NotificationManager $notification)
    {
        $this->registry = $registry;
        $this->notification = $notification;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            'workflow.invoice.entered' => 'onWorkflowTransitionApplied',
        ];
    }

    public function onWorkflowTransitionApplied(Event $event)
    {
        /** @var Invoice $invoice */
        $invoice = $event->getSubject();

        if (Graph::TRANSITION_PAY === $event->getTransition()->getName()) {
            $invoice->setPaidDate(Carbon::now());
        }

        if (Graph::TRANSITION_ARCHIVE === $event->getTransition()->getName()) {
            $invoice->archive();
        }

        $em = $this->registry->getManager();
        $em->persist($invoice);
        $em->flush();

        $this->notification->sendNotification('invoice_status_update', new InvoiceStatusNotification(['invoice' => $invoice]));
    }
}
