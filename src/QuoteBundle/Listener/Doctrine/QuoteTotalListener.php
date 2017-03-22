<?php

declare(strict_types=1);
/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\QuoteBundle\Listener\Doctrine;

use CSBill\CoreBundle\Util\ArrayUtil;
use CSBill\QuoteBundle\Entity\Quote;
use Doctrine\ORM\Event\LifecycleEventArgs;

class QuoteTotalListener
{
    /**
     * @param LifecycleEventArgs $event
     */
    public function postLoad(LifecycleEventArgs $event)
    {
        $entity = $event->getEntity();

        if ($entity instanceof Quote && count($entity->getUsers()) > 0) {
            $entityManager = $event->getEntityManager();

            $repository = $entityManager->getRepository('CSBillClientBundle:Contact');

            $criteria = [
                'id' => ArrayUtil::column($entity->getUsers(), 'id'),
            ];

            $contacts = $repository->findBy($criteria);

            $entity->setUsers($contacts);
        }
    }
}
