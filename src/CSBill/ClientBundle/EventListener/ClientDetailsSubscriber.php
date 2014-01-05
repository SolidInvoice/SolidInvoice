<?php

namespace CSBill\ClientBundle\EventListener;

use CSBill\ClientBundle\Entity\Contact;
use CSBill\ClientBundle\Entity\ContactDetail;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;

class ClientDetailsSubscriber implements EventSubscriber
{
    public function getSubscribedEvents()
    {
        return array(
            'prePersist',
            'preUpdate',
        );
    }

    public function prePersist(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();

        if ($entity instanceof ContactDetail) {
            $entity = $entity->getContact();
        }

        if ($entity instanceof Contact) {
            if (count($entity->getDetails()) > 0) {
                $primaryType = array();

                foreach ($entity->getDetails() as $detail) {
                    /** @var \CSBill\ClientBundle\Entity\ContactDetail $detail */
                    $type = $detail->getType();

                    if (!array_key_exists($type->getName(), $primaryType)) {
                        $detail->setPrimary(true);
                        $primaryType[$type->getName()] = $type;
                    }
                }

            }
        }
    }

    public function preUpdate(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();

        if ($entity instanceof Contact) {

            $primaryType = array();

            foreach ($entity->getDetails() as $detail) {
                $type = $detail->getType();

                if ($detail->isPrimary()) {
                    $primaryType[$type->getName()] = $type;
                    continue;
                }

                if (!array_key_exists($type->getName(), $primaryType)) {
                    $detail->setPrimary(true);
                    $primaryType[$type->getName()] = $type;
                }
            }
        }
    }
}
