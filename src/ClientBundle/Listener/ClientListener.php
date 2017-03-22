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

namespace CSBill\ClientBundle\Listener;

use CSBill\ClientBundle\Entity\Client;
use CSBill\ClientBundle\Notification\ClientCreateNotification;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class ClientListener implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @param LifecycleEventArgs $event
     */
    public function postPersist(LifecycleEventArgs $event)
    {
        $entity = $event->getEntity();

        if (!$entity instanceof Client) {
            return;
        }

        // client is created
        $notification = new ClientCreateNotification(['client' => $entity]);

        $this->container
            ->get('notification.manager')
            ->sendNotification('client_create', $notification);
    }

    /**
     * @param LifecycleEventArgs $event
     */
    public function postUpdate(LifecycleEventArgs $event)
    {
        $entity = $event->getEntity();

        if (!$entity instanceof Client) {
            return;
        }

        $entityChangeSet = $event->getEntityManager()->getUnitOfWork()->getEntityChangeSet($entity);

        $em = $event->getEntityManager();
        $em->getRepository('CSBillInvoiceBundle:Invoice')->updateCurrency($entity);

        // Only update the currencies when the client currency changed
        if (array_key_exists('currency', $entityChangeSet)) {
            $em = $event->getEntityManager();

            $em->getRepository('CSBillInvoiceBundle:Invoice')->updateCurrency($entity);
            $em->getRepository('CSBillQuoteBundle:Quote')->updateCurrency($entity);
            $em->getRepository('CSBillPaymentBundle:Payment')->updateCurrency($entity);
            $em->getRepository('CSBillClientBundle:Credit')->updateCurrency($entity);
        }
    }
}
