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

namespace SolidInvoice\InvoiceBundle\Listener;

use Carbon\Carbon;
use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\InvoiceBundle\Model\Graph;
use SolidInvoice\InvoiceBundle\Notification\InvoiceStatusNotification;
use SolidInvoice\NotificationBundle\Notification\NotificationManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\Event;
use Symfony\Component\Workflow\Transition;

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
            'workflow.recurring_invoice.enter' => 'onWorkflowTransitionApplied',
        ];
    }

    public function onWorkflowTransitionApplied(Event $event)
    {
        /** @var Invoice|RecurringInvoice $invoice */
        $invoice = $event->getSubject();

        if (($transition = $event->getTransition()) instanceof Transition) {
            if (Graph::TRANSITION_PAY === $transition->getName()) {
                $invoice->setPaidDate(Carbon::now());
            }

            if (Graph::TRANSITION_ARCHIVE === $transition->getName()) {
                $invoice->archive();
            }
        }

        $em = $this->registry->getManager();
        $em->persist($invoice);
        $em->flush();

        if (Graph::STATUS_NEW !== $invoice->getStatus()) {
            $this->notification->sendNotification('invoice_status_update', new InvoiceStatusNotification(['invoice' => $invoice]));
        }
    }
}
