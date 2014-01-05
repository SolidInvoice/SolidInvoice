<?php

namespace CSBill\QuoteBundle\Listener\Doctrine;

use Doctrine\ORM\Event\LifecycleEventArgs;
use JMS\DiExtraBundle\Annotation as DI;
use CSBill\QuoteBundle\Entity\Quote;

/**
 * @DI\DoctrineListener(
 *     events = {"postLoad"},
 *     connection = "default",
 *     lazy = true,
 *     priority = 0
 * )
 *
 * @author pierre
 */
class QuoteTotalListener
{
    public function postLoad(LifecycleEventArgs $event)
    {
        $em = $event->getEntityManager();
        $entity = $event->getEntity();

        if ($entity instanceof Quote) {
            if (count($entity->getUsers()) > 0) {
                $contacts = $em->getRepository('CSBillClientBundle:Contact')
                               ->findById(array_map(function ($item) {
                                        return $item->getId();
                                    },
                                    $entity->getUsers()->toArray()
                                )
                    );
                $entity->setUsers($contacts);
            }
        }
    }
}
