<?php

/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\InvoiceBundle\Form\EventListener;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use CSBill\InvoiceBundle\Entity\Invoice;
use Symfony\Component\Validator\Constraints\NotBlank;

class InvoiceUsersSubscriber implements EventSubscriberInterface
{
    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            FormEvents::PRE_SET_DATA => 'preSetData',
            FormEvents::PRE_SUBMIT => 'preSetData'
        );
    }

    /**
     * @param FormEvent $event
     */
    public function preSetData(FormEvent $event)
    {
        $data = $event->getData();

        if (null === $data) {
            return;
        }

        if ($data instanceof Invoice) {
            $clientId = !is_null($data->getClient()) ? $data->getClient()->getId() : null;
        } else {
            $clientId = isset($data['client']) ? $data['client'] : null;
        }

        if (!empty($clientId)) {
            $form = $event->getForm();

            $form->add(
                'users',
                'entity',
                array(
                    'constraints' => array(
                        new NotBlank()
                    ),
                    'multiple' => true,
                    'expanded' => true,
                    'class' => 'CSBillClientBundle:Contact',
                    'query_builder' => function (EntityRepository $repo) use ($clientId) {
                        $qb = $repo->createQueryBuilder('c')
                            ->where('c.client = :client')
                            ->setParameter('client', $clientId);

                        return $qb;
                    }
                )
            );
        }
    }
}
