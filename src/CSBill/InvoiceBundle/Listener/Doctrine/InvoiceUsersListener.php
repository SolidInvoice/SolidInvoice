<?php
/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\InvoiceBundle\Listener\Doctrine;

use Doctrine\ORM\Event\LifecycleEventArgs;
use CSBill\InvoiceBundle\Entity\Invoice;

class InvoiceUsersListener
{
    /**
     * @param LifecycleEventArgs $event
     */
    public function postLoad(LifecycleEventArgs $event)
    {
        $em = $event->getEntityManager();
        $entity = $event->getEntity();

        if ($entity instanceof Invoice) {
            if (count($entity->getUsers()) > 0) {
                $contacts = $em->getRepository('CSBillClientBundle:Contact')
                    ->findById(array_map(function ($item) {
                        return $item->getId();
                    }, $entity->getUsers()->toArray()));
                $entity->setUsers($contacts);
            }
        }
    }
}
